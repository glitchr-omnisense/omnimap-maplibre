/**
 * MapLibre's driver for omnimap's script (assets/omnimap.js of omniscient/omnimap):
 * draws the description MapLibreProvider::describe() gave.
 *
 *   import { register } from 'omnimap';
 *   import * as driver from 'omnimap/maplibre/assets/maplibre.js';
 *   register('maplibre', driver);
 */
export async function draw(element, description, options = {}) {
  // Replace with the map library's own drawing; the description is plain data.
  element.dataset.omnimapProvider = description.provider;
  element.setAttribute('role', 'img');
  element.setAttribute('aria-label', options.label ?? 'Map');
  return { element, description };
}
