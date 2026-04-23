import "./bootstrap";
import Chart from "chart.js/auto";
import "leaflet/dist/leaflet.css";
import "@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css";
import L from "leaflet";
import "@geoman-io/leaflet-geoman-free";
import * as turf from "@turf/turf";

window.Chart = Chart;
window.L = L;
window.turf = turf;

// Fix Leaflet default marker icon paths broken by Vite bundling
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL("leaflet/dist/images/marker-icon-2x.png", import.meta.url).href,
    iconUrl: new URL("leaflet/dist/images/marker-icon.png", import.meta.url).href,
    shadowUrl: new URL("leaflet/dist/images/marker-shadow.png", import.meta.url).href,
});

// Componente Alpine para el mapa de zonas de envío.
// Registrado aquí (antes de que Alpine arranque vía livewire.js)
// para que x-data="deliveryZoneMap()" lo encuentre correctamente.
document.addEventListener("alpine:init", () => {
    Alpine.data("deliveryZoneMap", () => ({
        map: null,
        drawnLayer: null,
        zonesGroup: null,

        async init() {
            await this.$nextTick();

            const storeLat = parseFloat(document.getElementById("delivery-map")?.dataset.lat || "4.7109886");
            const storeLng = parseFloat(document.getElementById("delivery-map")?.dataset.lng || "-74.072092");

            this.map = L.map("delivery-map").setView([storeLat, storeLng], 13);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors",
                maxZoom: 19,
            }).addTo(this.map);

            this.map.pm.addControls({
                position: "topleft",
                drawMarker: false,
                drawCircleMarker: false,
                drawPolyline: false,
                drawCircle: false,
                drawRectangle: true,
                drawPolygon: true,
                editMode: false,
                dragMode: false,
                cutPolygon: false,
                rotateMode: false,
                removalMode: false,
            });

            this.map.pm.setGlobalOptions({ snappable: true, snapDistance: 10 });
            this.zonesGroup = L.layerGroup().addTo(this.map);

            this.map.on("pm:create", (e) => {
                const drawn = e.layer;
                this.map.removeLayer(drawn);

                const drawnGeoJson = drawn.toGeoJSON();
                const finalPolygon = this.subtractExistingZones(drawnGeoJson);

                if (!finalPolygon) {
                    alert("El polígono dibujado queda completamente cubierto por zonas existentes.");
                    return;
                }

                if (this.drawnLayer) {
                    this.map.removeLayer(this.drawnLayer);
                }

                this.drawnLayer = L.geoJSON(finalPolygon, {
                    style: { color: "#F59E0B", fillOpacity: 0.35, weight: 2, dashArray: "6" },
                }).addTo(this.map);

                this.$wire.set("polygon", finalPolygon);
            });

            await this.reloadZones();
        },

        subtractExistingZones(drawnGeoJson) {
            const existing = this.getExistingPolygons();
            if (existing.length === 0) return drawnGeoJson;

            try {
                let unioned = existing[0];
                for (let i = 1; i < existing.length; i++) {
                    const u = turf.union(turf.featureCollection([unioned, existing[i]]));
                    if (u) unioned = u;
                }
                return turf.difference(turf.featureCollection([drawnGeoJson, unioned])) || drawnGeoJson;
            } catch (err) {
                console.warn("turf.difference error:", err);
                return drawnGeoJson;
            }
        },

        getExistingPolygons() {
            const polygons = [];
            this.zonesGroup.eachLayer((layer) => {
                if (layer.toGeoJSON) {
                    polygons.push(layer.toGeoJSON());
                }
            });
            return polygons;
        },

        async reloadZones() {
            if (!this.map) return;
            try {
                const res = await fetch("/api/delivery-zones", {
                    headers: { Accept: "application/json" },
                });
                const zones = await res.json();
                this.zonesGroup.clearLayers();
                zones.forEach((zone) => {
                    if (!zone.polygon) return;
                    L.geoJSON(zone.polygon, {
                        style: {
                            color: zone.color,
                            fillColor: zone.color,
                            fillOpacity: zone.active ? 0.25 : 0.08,
                            weight: 2,
                        },
                    })
                        .bindTooltip(
                            zone.name + " — $" + (zone.price_cents / 100).toLocaleString("es-CO"),
                            { permanent: false, sticky: true }
                        )
                        .addTo(this.zonesGroup);
                });
            } catch (err) {
                console.error("Error cargando zonas:", err);
            }
        },

        loadPolygon(polygon, color) {
            if (this.drawnLayer) {
                this.map.removeLayer(this.drawnLayer);
                this.drawnLayer = null;
            }
            if (!polygon) return;
            this.drawnLayer = L.geoJSON(polygon, {
                style: { color: color || "#F59E0B", fillOpacity: 0.35, weight: 2, dashArray: "6" },
            }).addTo(this.map);
            try {
                this.map.fitBounds(this.drawnLayer.getBounds(), { padding: [20, 20] });
            } catch (_) {}
        },

        clearDrawing() {
            if (this.drawnLayer) {
                this.map.removeLayer(this.drawnLayer);
                this.drawnLayer = null;
            }
        },
    }));
});
