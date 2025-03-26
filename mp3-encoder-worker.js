importScripts('lame.min.js');

let mp3Encoder;
let maxSamples = 1152;

self.onmessage = function (e) {
    if (e.data.type === 'init') {
        let channels = 1;
        let sampleRate = e.data.config.sampleRate;
        let kbps = 128;
        mp3Encoder = new lamejs.Mp3Encoder(channels, sampleRate, kbps);
    } else if (e.data.type === 'encode') {
        let mp3Data = [];
        let samples = e.data.buffer[0];
        let remaining = samples.length;
        for (let i = 0; remaining >= maxSamples; i += maxSamples) {
            let mono = samples.subarray(i, i + maxSamples);
            let mp3buf = mp3Encoder.encodeBuffer(mono);
            if (mp3buf.length > 0) {
                mp3Data.push(new Int8Array(mp3buf));
            }
            remaining -= maxSamples;
        }
        self.postMessage({ type: 'data', data: mp3Data });
    } else if (e.data.type === 'finish') {
        let mp3buf = mp3Encoder.flush();
        if (mp3buf.length > 0) {
            self.postMessage({ type: 'done', blob: [new Uint8Array(mp3buf)] });
        }
    }
};