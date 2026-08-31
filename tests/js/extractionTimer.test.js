import assert from 'node:assert/strict';
import { test } from 'node:test';
import { useExtractionTimer } from '../../resources/js/composables/useExtractionTimer.js';

test('elapsed time advances, stops, resets, and preserves separate phase snapshots', (t) => {
  let now = 1000;
  let tick;
  const cleared = [];
  t.mock.method(performance, 'now', () => now);
  t.mock.method(globalThis, 'setInterval', (callback) => { tick = callback; return 42; });
  t.mock.method(globalThis, 'clearInterval', (id) => cleared.push(id));
  const timer = useExtractionTimer();
  assert.equal(timer.timing.started, false);
  timer.start();
  now = 4500;
  tick();
  assert.equal(timer.timing.seconds, 3.5);
  timer.record('initial', { logo: 1 });
  timer.record('details', { logo: 2, description: 4 });
  timer.record('details', { logo: 3, invalid: -1, bad: NaN });
  assert.deepEqual({ ...timer.timing.phases }, {
    'initial:logo': 1, 'details:logo': 3, 'details:description': 4,
  });
  now = 5000;
  timer.stop();
  assert.equal(timer.timing.seconds, 4);
  assert.equal(timer.timing.running, false);
  assert.ok(cleared.includes(42));
  now = 9000;
  timer.stop();
  timer.record('details', { logo: 99 });
  assert.equal(timer.timing.seconds, 4);
  assert.equal(timer.timing.phases['details:logo'], 3);
  timer.start();
  assert.equal(timer.timing.seconds, 0);
  assert.deepEqual({ ...timer.timing.phases }, {});
  now = 9500;
  timer.stop();
  assert.equal(timer.timing.seconds, 0.5);
});
