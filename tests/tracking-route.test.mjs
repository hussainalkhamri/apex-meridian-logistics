import test from 'node:test';
import assert from 'node:assert/strict';
import { airRoute, routeLine, routePosition, routeDistance, connectRouteEndpoints } from '../resources/js/tracking-route.js';
import searoute from 'searoute-js';

test('estimated position follows distance rather than point count', () => {
    const line = routeLine({type:'LineString',coordinates:[[0,0],[1,0],[10,0]]});
    assert.ok(Math.abs(routePosition(line, 0.5).coordinates[0] - 5) < 0.01);
    assert.equal(routePosition(line, null), null);
    assert.deepEqual(routePosition(line, 0).coordinates, [0,0]);
    assert.ok(Math.abs(routePosition(line, 1).coordinates[0] - 10) < 0.01);
});

test('air route handles the date line without a line across the whole map', () => {
    const line = airRoute([170,35], [-170,35]);
    const lons = line.geometry.coordinates.map(point=>point[0]);
    assert.ok(Math.max(...lons)-Math.min(...lons) < 25);
    assert.ok(routeDistance(line) < 2500);
    assert.ok(Math.abs(routePosition(line, 0.5).coordinates[0] - 180) < 1);
});

test('sea route uses maritime waypoints between Jeddah and Aden', () => {
    const point = coordinates=>({type:'Feature',properties:{},geometry:{type:'Point',coordinates}});
    const sea = searoute(point([39.166667,21.483333]),point([45.033333,12.8]),'kilometers');
    assert.ok(sea.geometry.coordinates.length > 3);
    const route = routeLine(sea.geometry);
    assert.ok(routeDistance(route) > 1000);
    assert.ok(routePosition(route,0.5).coordinates.every(Number.isFinite));
});

test('approximate network connections preserve endpoints and reject remote snaps', () => {
    const line = routeLine({ type: 'LineString', coordinates: [[39, 21], [45, 12]] });
    const connected = connectRouteEndpoints(line, [39.1, 21.1], [45.1, 12.1], 200);
    assert.deepEqual(routePosition(connected, 0).coordinates, [39.1, 21.1]);
    assert.ok(Math.abs(routePosition(connected, 1).coordinates[0] - 45.1) < 0.001);
    assert.equal(connectRouteEndpoints(line, [46.7, 24.7], [45.1, 12.1], 200), null);
    assert.throws(() => routeLine({ type: 'LineString', coordinates: [[1], [2, 4]] }));
    assert.throws(() => routeLine({ type: 'Polygon', coordinates: [[1, 2], [3, 4]] }));
});
