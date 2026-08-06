import test from 'node:test';
import assert from 'node:assert/strict';
import { createRenderScheduler } from '../public/js/renderScheduler.js';

test('coalesces rapid render requests into a single callback', async () => {
  let count = 0;
  const schedule = createRenderScheduler(() => {
    count += 1;
  });

  schedule();
  schedule();
  schedule();

  await new Promise((resolve) => setTimeout(resolve, 10));
  assert.equal(count, 1);
});
