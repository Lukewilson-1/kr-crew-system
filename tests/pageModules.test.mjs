import test from 'node:test';
import assert from 'node:assert/strict';
import { getPageModuleMap } from '../public/js/pageModules.mjs';

test('page module map exposes all navigation pages', () => {
  const modules = getPageModuleMap();
  assert.deepEqual(Object.keys(modules).sort(), ['dashboard', 'monthly', 'reports', 'rest', 'roster']);
  Object.values(modules).forEach((module) => {
    assert.equal(typeof module.init, 'function');
  });
});
