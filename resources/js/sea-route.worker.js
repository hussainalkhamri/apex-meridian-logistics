import searoute from 'searoute-js';

self.onmessage = ({ data }) => {
    try {
        const point = (coordinates) => ({ type: 'Feature', properties: {}, geometry: { type: 'Point', coordinates } });
        const result = searoute(point(data.origin), point(data.destination), 'kilometers');
        self.postMessage({ geometry: result?.geometry ?? null });
    } catch (error) {
        console.warn('Maritime route calculation failed:', error.message);
        self.postMessage({ geometry: null });
    }
};
