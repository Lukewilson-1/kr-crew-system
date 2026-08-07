import test from 'node:test';
import assert from 'node:assert/strict';
import {
  getDesignationLabel,
  isDesignationRestEligible,
  normalizeDesignation,
  setDesignationRegistry,
} from '../public/js/constants.js';

test('designation registry blocks non-rest roles while preserving aliases', () => {
  setDesignationRegistry({
    locomotive_driver: {
      id: 'locomotive_driver',
      label: 'Locomotive Driver',
      aliases: ['driver'],
      restEligible: true,
      order: 10,
    },
    shunter_driver: {
      id: 'shunter_driver',
      label: 'Shunter Driver',
      aliases: ['shunter'],
      restEligible: true,
      order: 20,
    },
    lio: {
      id: 'lio',
      label: 'LIO',
      aliases: [],
      restEligible: true,
      order: 30,
    },
  });

  assert.equal(normalizeDesignation('driver'), 'locomotive_driver');
  assert.equal(getDesignationLabel('shunter'), 'Shunter Driver');
  assert.equal(isDesignationRestEligible('Locomotive Driver'), true);
  assert.equal(isDesignationRestEligible('Shunter Driver'), false);
  assert.equal(isDesignationRestEligible('LIO'), false);
});
