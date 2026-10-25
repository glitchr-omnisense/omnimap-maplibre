/**
 * omnimap's driver for MapLibre: draws the description of
 * Omnimap\MapLibre\MapLibreProvider::describe() with MapLibre GL JS - loaded
 * from description.library (a script and its stylesheet with their integrity,
 * or an ES module) unless window.maplibregl is there already - from the
 * description's vector style, its sources' attribution shown. Markers are MapLibre's
 * own (an inline SVG: no image to fetch), their title as the marker's label.
 * The map keeps its drawing buffer (options.preserveDrawingBuffer) so
 * omnimap's capture.js can take its picture.
 *
 *   import { register } from '.../omnimap/assets/omnimap.js';
 *   import maplibre from '.../omnimap/maplibre/assets/maplibre.js';
 *   register('maplibre', maplibre);
 */
export async function draw(element, description, { loadScript, loadStyle }) {
  const library = description.library || {};
  let maplibregl = window.maplibregl;
  if (!maplibregl) {
    if (library.style) loadStyle(library.style, { integrity: library.styleIntegrity, crossorigin: library.styleIntegrity ? 'anonymous' : null });
    if (library.module) {
      const module = await import(/* webpackIgnore: true */ library.module);
      maplibregl = module.default || module;
    } else if (library.script) {
      await loadScript(library.script, { integrity: library.scriptIntegrity, crossorigin: library.scriptIntegrity ? 'anonymous' : null });
      maplibregl = window.maplibregl;
    }
    if (!maplibregl) throw new Error('omnimap: no MapLibre GL JS to load.');
  }
  const view = description.view;
  const options = description.options || {};

  const map = new maplibregl.Map({
    container: element,
    style: description.style,
    center: [view.center.lng, view.center.lat],
    zoom: view.zoom,
    // The style's sources carry their attribution; description.attribution is what a kept picture shows.
    attributionControl: { compact: false },
    canvasContextAttributes: { preserveDrawingBuffer: options.preserveDrawingBuffer !== false },
    preserveDrawingBuffer: options.preserveDrawingBuffer !== false,
    interactive: view.controls !== false,
    ...(options.map || {}),
  });
  if (view.controls !== false) map.addControl(new maplibregl.NavigationControl({ showCompass: false }));
  (view.markers || []).forEach((marker) => {
    const pin = new maplibregl.Marker({ color: marker.color || '#d33b3b' }).setLngLat([marker.position.lng, marker.position.lat]);
    if (marker.title) {
      pin.getElement().setAttribute('aria-label', marker.title);
      pin.getElement().title = marker.title;
    }
    pin.addTo(map);
  });
  map.once('idle', () => window.dispatchEvent(new Event('idle')));

  return map;
}

export default { draw };
