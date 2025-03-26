/**
 * Web Worker for encoding audio data to MP3 using lamejs.
 * 
 * This worker handles initialization, encoding, and finalization of MP3 audio encoding.
 * It processes raw PCM samples and converts them into MP3 format.
 * 
 * @file mp3EncoderWorker.js
 * @module mp3EncoderWorker
 */

importScripts('lame.min.js');

/** @type {lamejs.Mp3Encoder} */
let mp3Encoder;

/** @const {number} Maximum number of samples per encoding block */
const maxSamples = 1152;

self.onmessage = function (e) {
    if (e.data.type === 'init') {
        /**
         * Initializes the MP3 encoder with the given configuration.
         * @param {Object} e.data.config - Configuration object containing sampleRate.
         * @param {number} e.data.config.sampleRate - The sample rate of the audio.
         */
        let channels = 1;
        let sampleRate = e.data.config.sampleRate;
        let kbps = 128;
        mp3Encoder = new lamejs.Mp3Encoder(channels, sampleRate, kbps);
    } else if (e.data.type === 'encode') {
        /**
         * Encodes PCM audio data to MP3.
         * @param {Float32Array[]} e.data.buffer - The buffer containing PCM samples.
         */
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
        /**
         * Finalizes the MP3 encoding and flushes remaining data.
         */
        let mp3buf = mp3Encoder.flush();
        if (mp3buf.length > 0) {
            self.postMessage({ type: 'done', blob: [new Uint8Array(mp3buf)] });
        }
    }
};