import along from '@turf/along';
import length from '@turf/length';
import greatCircle from '@turf/great-circle';

export function routeLine(geometry) {
    if (!['LineString', 'MultiLineString'].includes(geometry?.type)) throw new Error('Invalid route geometry');
    const source = geometry.type === 'MultiLineString' ? geometry.coordinates.flat() : geometry.coordinates;
    const coordinates = [];
    for (const point of source || []) {
        if (!Array.isArray(point) || point.length !== 2 || !point.every(Number.isFinite) || Math.abs(point[1]) > 90) throw new Error('Invalid route coordinate');
        let longitude = point[0];
        if (coordinates.length) {
            const previous = coordinates[coordinates.length - 1][0];
            while (longitude - previous > 180) longitude -= 360;
            while (longitude - previous < -180) longitude += 360;
        }
        coordinates.push([longitude, point[1]]);
    }
    if (coordinates.length < 2) throw new Error('Missing route geometry');
    return { type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates } };
}

export function airRoute(origin, destination) {
    return routeLine(greatCircle(origin, destination, { npoints: 128 }).geometry);
}

export function connectRouteEndpoints(line, origin, destination, maxGapKm) {
    const points = line.geometry.coordinates;
    const gap = (a, b) => routeDistance(routeLine({ type: 'LineString', coordinates: [a, b] }));
    if (gap(origin, points[0]) > maxGapKm || gap(destination, points[points.length - 1]) > maxGapKm) return null;
    return routeLine({ type: 'LineString', coordinates: [origin, ...points, destination] });
}

export function routePosition(line, fraction) {
    if (fraction === null || !Number.isFinite(fraction)) return null;
    const distance = length(line, { units: 'kilometers' });
    const point = along(line, distance * Math.max(0, Math.min(1, fraction)), { units: 'kilometers' });
    const coordinates = [...point.geometry.coordinates];
    const routePoints = line.geometry.coordinates;
    const center = (routePoints[0][0] + routePoints[routePoints.length - 1][0]) / 2;
    while (coordinates[0] - center > 180) coordinates[0] -= 360;
    while (coordinates[0] - center < -180) coordinates[0] += 360;
    return { coordinates, distance };
}

export function routeDistance(line) {
    return length(line, { units: 'kilometers' });
}
