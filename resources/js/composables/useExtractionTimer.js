import { reactive } from 'vue';

const timing = reactive({ started: false, running: false, seconds: 0, phases: {} });
let startedAt = 0;
let interval = null;

export function useExtractionTimer() {
  const tick = () => { timing.seconds = (performance.now() - startedAt) / 1000; };
  const stop = () => {
    if (timing.running) tick();
    clearInterval(interval);
    interval = null;
    timing.running = false;
  };
  const start = () => {
    stop();
    startedAt = performance.now();
    Object.assign(timing, { started: true, running: true, seconds: 0, phases: {} });
    interval = setInterval(tick, 100);
  };
  const record = (scope, phases) => {
    if (!timing.running || !phases || typeof phases !== 'object') return;
    Object.entries(phases).forEach(([phase, seconds]) => {
      if (Number.isFinite(seconds) && seconds >= 0) timing.phases[`${scope}:${phase}`] = seconds;
    });
  };
  return { timing, start, stop, record };
}
