# ADR-001: In-Browser Maritime Route Calculation via Web Workers

**Status:** Accepted  
**Deciders:** Hussain Al-Khamri (Software Architect)  
**Date:** October 15, 2026  
**Technical Domain:** Geographic Information Systems (GIS), Client Performance, Infrastructure Cost Optimization  

---

## 1. Context & Problem Statement

Maritime freight tracking and quotation portals must calculate realistic oceanic shipping paths between any pair of global ports (e.g., Shanghai to Rotterdam). Because ships navigate through defined ocean lanes, straits (Malacca, Hormuz, Bab-el-Mandeb), and canals (Suez, Panama), simple Euclidean straight lines or great-circle arcs are inaccurate and unprofessional.

Traditional logistics platforms calculate these routes on the server side using PostGIS with the `pgRouting` extension, or specialized routing microservices (e.g., OSRM/Valhalla). 

However, running a dedicated server-side GIS engine incurs:
1. Significant recurring cloud infrastructure costs (PostgreSQL + PostGIS instances with large RAM footprints).
2. Network latency: Every port selection requires an asynchronous round-trip HTTP request (300ms–1,500ms), delaying the user experience.
3. Operational complexity: Deploying and maintaining geospatial databases and spatial index topologies across multiple environments (local development, staging, production).

The engineering challenge was: **How can we provide instantaneous, highly accurate oceanic route calculations for 700+ global ports with zero recurring GIS infrastructure cost and zero network latency?**

---

## 2. Considered Alternatives

### Option A: Server-Side PostGIS + `pgRouting`
* **Pros:** Standard GIS pattern; can handle dynamic marine weather constraints or real-time canal blockage notices.
* **Cons:** High memory overhead (min 2GB–4GB RAM); adds database dependency; introduces network latency for international users; requires custom geospatial Docker containers.

### Option B: Third-Party Maritime Routing API (e.g., Searoutes.com, FleetMon)
* **Pros:** Zero local infrastructure setup; high accuracy.
* **Cons:** Expensive recurring SaaS subscription fees per API call; third-party vendor lock-in; rate limiting; failure when offline or during network disruptions.

### Option C: Client-Side Web Worker with Pre-Compiled GeoJSON Network (Chosen)
* **Pros:**
  * **Zero Server Load:** Route computations run entirely on the client's CPU.
  * **Zero Latency:** Computations take 18ms–45ms directly in browser memory.
  * **Zero Cloud Cost:** Spatial network is bundled as static, cacheable GeoJSON (~2.1 MB).
  * **Offline/PWA Capability:** Functions completely without an internet connection once loaded.
  * **Responsive UI:** Computation is offloaded to an HTML5 Web Worker (`searoute.worker.js`), ensuring zero main-thread UI stutter.
* **Cons:**
  * Initial static asset transfer of ~2.1 MB (mitigated by HTTP/2 gzip compression down to ~450 KB and immutable browser caching).
  * Static network cannot incorporate live dynamic sea weather without sending route deltas.

---

## 3. Decision Outcome

We decided to implement **Option C: Client-Side Web Worker with Pre-Compiled GeoJSON Network**.

### Implementation Details:
1. A static, curated GeoJSON graph of maritime shipping routes and navigable water channels is served from `public/data/seaways.geojson`.
2. A background Web Worker (`searoute.worker.js`) fetches and caches the GeoJSON network upon initial map load.
3. When the user interacts with port selection dropdowns:
   - The main Leaflet thread sends port coordinates to the Web Worker via `postMessage()`.
   - The Web Worker snaps coordinates to the nearest navigable sea node using `@turf/nearest-point-on-line`.
   - The worker runs a Dijkstra shortest path algorithm across the graph edges.
   - The path is smoothed using great-circle geodesic interpolation.
   - The resulting GeoJSON `MultiLineString` and nautical distance are returned to Leaflet via `postMessage()`.
4. Leaflet draws the animated sea route on the canvas layer at 60 FPS.

---

## 4. Consequences & Trade-offs

### Positive Consequences
* **Sub-50ms Response Time:** Route calculations feel instantaneous to users.
* **Zero Infrastructure Overhead:** The application backend requires only standard PHP/Laravel runtime with zero GIS extensions, enabling simple deployment on any shared, VPS, or container host.
* **100% Testable in Isolation:** Algorithmic correctness is verified via 17 automated Node.js unit tests without requiring database fixtures.

### Negative Consequences / Mitigations
* *Network Download:* The initial page load must download the compressed seaways GeoJSON file (~450 KB gzip).  
  *Mitigation:* The asset is loaded asynchronously in the background only when the map view is requested, and cached indefinitely via `Cache-Control: max-age=31536000, immutable`.
