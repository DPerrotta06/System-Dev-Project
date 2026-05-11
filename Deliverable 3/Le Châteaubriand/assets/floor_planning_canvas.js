document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById("floor-canvas");
    const ctx = canvas.getContext("2d");
    let selectedTables = {};
    let currentLayout = document.body.dataset.hall || "grand_salon";

    const COLOR = {
        bg:            "#0e0c09",
        room:          "#1a1712",
        roomStroke:    "#C9A84C",
        feature:       "#111008",
        featureStroke: "rgba(201,168,76,0.45)",
        featureText:   "rgba(245,240,232,0.4)",
        tableFill:     "#1a1712",
        tableStroke:   "rgba(201,168,76,0.5)",
        tableText:     "rgba(245,240,232,0.7)",
        selected:      "rgba(201,168,76,0.22)",
        selectedStroke:"#C9A84C",
        selectedText:  "#C9A84C",
        label:         "rgba(245,240,232,0.55)",
    };

    const R = 20; // table radius

    // ─────────────────────────────────────────────
    // ROYAL HALL  (matches Royal_Map.jpg)
    // 28 tables arranged around a central dance floor
    // Honor table top-center, Entrance + Stage bottom
    // Bar/servery nook on left wall
    // ─────────────────────────────────────────────
    const royal = (() => {
        // Room rect
        const rx = 50, ry = 50, rw = 700, rh = 490;
        // Dance floor – 25'×25', roughly centered, shifted slightly left
        const dfx = rx + 200, dfy = ry + 130, dfw = 300, dfh = 250;
        // Honor table – top center above dance floor
        const htx = dfx + 20, hty = ry + 68, htw = dfw - 40, hth = 26;

        // Tables – matching the photo layout closely
        const tables = [
            // TOP ROW (flanking honor table)
            { x: rx+80,  y: ry+80  }, // 1 top-left
            { x: rx+155, y: ry+80  }, // 2
            { x: rx+rw-155, y: ry+80  }, // 3
            { x: rx+rw-80,  y: ry+80  }, // 4 top-right

            // LEFT COLUMN (outer wall)
            { x: rx+55,  y: ry+175 }, // 5
            { x: rx+55,  y: ry+275 }, // 6
            { x: rx+55,  y: ry+375 }, // 7

            // LEFT INNER (beside dance floor)
            { x: dfx-60, y: dfy+50  }, // 8
            { x: dfx-60, y: dfy+130 }, // 9
            { x: dfx-60, y: dfy+210 }, // 10

            // RIGHT COLUMN (outer wall)
            { x: rx+rw-55, y: ry+175 }, // 11
            { x: rx+rw-55, y: ry+275 }, // 12
            { x: rx+rw-55, y: ry+375 }, // 13

            // RIGHT INNER (beside dance floor)
            { x: dfx+dfw+60, y: dfy+50  }, // 14
            { x: dfx+dfw+60, y: dfy+130 }, // 15
            { x: dfx+dfw+60, y: dfy+210 }, // 16

            // BOTTOM ROW (above entrance/stage)
            { x: rx+80,        y: ry+rh-65 }, // 17
            { x: rx+175,       y: ry+rh-65 }, // 18
            { x: dfx+40,       y: ry+rh-65 }, // 19
            { x: dfx+dfw/2,    y: ry+rh-65 }, // 20
            { x: dfx+dfw-40,   y: ry+rh-65 }, // 21
            { x: rx+rw-175,    y: ry+rh-65 }, // 22
            { x: rx+rw-80,     y: ry+rh-65 }, // 23

            // EXTRA TABLES (corners / additional)
            { x: rx+130, y: ry+175 }, // 24
            { x: rx+130, y: ry+275 }, // 25
            { x: rx+rw-130, y: ry+175 }, // 26
            { x: rx+rw-130, y: ry+275 }, // 27
            { x: dfx+dfw/2, y: dfy-55 }, // 28 above dance floor center
        ];

        return {
            tables,
            room:       { x: rx, y: ry, w: rw, h: rh },
            danceFloor: { x: dfx, y: dfy, w: dfw, h: dfh, label: "Dance Floor", sub: "(25'×25')" },
            honorTable: { x: htx, y: hty, w: htw, h: hth },
            podium:     null,
            stage:      { x: rx+rw/2+10, y: ry+rh-8, w: 140, h: 52, label: "STAGE", sub: "(10'×18')" },
            entrance:   { x: rx+rw/2-150, y: ry+rh-8, w: 110, h: 40, label: "ENTRANCE" },
            bar:        { x: rx-8, y: ry+180, w: 40, h: 140, label: "BAR" },
            djBooth:    null,
            label: "Royal Hall",
        };
    })();

    // ─────────────────────────────────────────────
    // GRAND SALON  (based on Grand_Salon_Map.jpg)
    // 35 tables, podium top-center, dance floor 18'×33'
    // DJ booth bottom-left, entrance bottom-right (dashed)
    // Two columns left + two columns right of dance floor
    // ─────────────────────────────────────────────
    const grand_salon = (() => {
        const rx = 50, ry = 50, rw = 710, rh = 605; // Room rect dimensions
        // Dance floor – 18'×33', taller than wide, center-right of room
        const dfx = rx + 280, dfy = ry + 120, dfw = 200, dfh = 340;
        // Honor table top, above dance floor
        const htx = dfx + 10, hty = ry + 60, htw = dfw - 20, hth = 26;
        // Podium above honor table
        const podx = dfx + dfw/2 - 50, pody = ry + 60, podw = 100, podh = 26;

        const tables = [
            // LEFT OUTER COLUMN
            { x: rx+110, y: ry+115 }, // 1
            { x: rx+110, y: ry+195 }, // 2
            { x: rx+110, y: ry+275 }, // 3
            { x: rx+110, y: ry+355 }, // 4
            { x: rx+110, y: ry+435 }, // 5

            // LEFT INNER COLUMN
            { x: rx+220, y: ry+115 }, // 6
            { x: rx+220, y: ry+195 }, // 7
            { x: rx+220, y: ry+275 }, // 8
            { x: rx+220, y: ry+355 }, // 9
            { x: rx+220, y: ry+435 }, // 10
            
            //BELOW LEFT INNER COLUMN
            { x: dfx+dfw/2-215, y: ry+150 }, // 11
            { x: dfx+dfw/2-215, y: ry+235 }, // 12
            { x: dfx+dfw/2-215, y: ry+315 }, // 13
            { x: dfx+dfw/2-215, y: ry+400 }, // 14
            { x: dfx+dfw/2-215,  y: ry+480 }, // 30
            

            // RIGHT INNER COLUMN
            { x: dfx+dfw+55, y: ry+115 }, // 15
            { x: dfx+dfw+55, y: ry+195 }, // 16
            { x: dfx+dfw+55, y: ry+275 }, // 17
            { x: dfx+dfw+55, y: ry+355 }, // 18
            { x: dfx+dfw+55, y: ry+435 }, // 19

            // RIGHT OUTER COLUMN
            { x: rx+rw-55, y: ry+115 }, // 20
            { x: rx+rw-55, y: ry+195 }, // 21
            { x: rx+rw-55, y: ry+275 }, // 22
            { x: rx+rw-55, y: ry+355 }, // 23
            { x: rx+rw-55, y: ry+435 }, // 24
            
            //BELOW RIGHT INNER COLUMN
            { x: dfx+dfw/2+215,  y: ry+150 }, // 25
            { x: dfx+dfw/2+215,  y: ry+235 }, // 26
            { x: dfx+dfw/2+215,  y: ry+315 }, // 27
            { x: dfx+dfw/2+215,  y: ry+400 }, // 28
            { x: dfx+dfw/2+215,  y: ry+480 }, // 29
            { x: dfx+dfw/2-15,  y: ry+480 }, // 30

            // Flanking dance floor center
            { x: dfx-60, y: dfy+230} , // 31
            { x: dfx+dfw/2-130, y: ry+500 }, // 32
            { x: rx+220, y: ry+540  },// 33
            { x: dfx+dfw+60, y: dfy+70  }, // 34 — already covered by right inner col
            { x: dfx+dfw+60, y: dfy+150 }, // 35
        ];

        return {
            tables,
            room:       { x: rx, y: ry, w: rw, h: rh },
            danceFloor: { x: dfx, y: dfy, w: dfw, h: dfh, label: "Piste de Dance", sub: "(18'×33')" },
            honorTable: { x: htx, y: hty, w: htw, h: hth },
            podium:     { x: podx, y: pody, w: podw, h: podh, label: "Honor Table", sub: "(8'×12')" },
            stage:      null,
            entrance:   { x: rx+rw-330, y: ry+rh+2, w: 100, h: 36, label: "ENTRANCE", dashed: true },
            bar:        null,
            djBooth:    { x: rx+290, y: ry+rh-70, w: 110, h: 55, label: "Espace DJ", sub: "(8'×12')" },
            label: "Grand Salon",
        };
    })();

    // ─────────────────────────────────────────────
    // PRINCESS  (smaller hall, 14 tables)
    // ─────────────────────────────────────────────
    const princess = (() => {
        const rx = 80, ry = 70, rw = 640, rh = 430;
        const dfx = rx+180, dfy = ry+110, dfw=280, dfh=200;
        const tables = [
            { x: rx+50,     y: ry+80  }, // 1
            { x: rx+50,     y: ry+175 }, // 2
            { x: rx+50,     y: ry+270 }, // 3
            { x: rx+50,     y: ry+365 }, // 4
            { x: rx+rw-50,  y: ry+80  }, // 5
            { x: rx+rw-50,  y: ry+175 }, // 6
            { x: rx+rw-50,  y: ry+270 }, // 7
            { x: rx+rw-50,  y: ry+365 }, // 8
            { x: rx+155,    y: ry+55  }, // 9
            { x: rx+270,    y: ry+55  }, // 10
            { x: rx+385,    y: ry+55  }, // 11
            { x: rx+500,    y: ry+55  }, // 12
            { x: rx+225,    y: ry+rh-50 }, // 13
            { x: rx+420,    y: ry+rh-50 }, // 14
        ];
        return {
            tables,
            room:       { x: rx, y: ry, w: rw, h: rh },
            danceFloor: { x: dfx, y: dfy, w: dfw, h: dfh, label: "Dance Floor", sub: "(18'×25')" },
            honorTable: { x: dfx+20, y: ry+65, w: dfw-40, h: 24 },
            podium:     null,
            stage:      null,
            entrance:   { x: rx+rw/2-55, y: ry+rh-5, w: 110, h: 32, label: "ENTRANCE", dashed: true },
            bar:        null,
            djBooth:    null,
            label: "Princess",
        };
    })();

    const layouts = { royal, grand_salon, princess };

    // ─────────────────────────────────────────────
    // DRAW
    // ─────────────────────────────────────────────
    function roundRect(x, y, w, h, r = 0) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function drawFeature(obj, label, sub, dashed = false) {
        ctx.fillStyle = COLOR.feature;
        ctx.fill();
        if (dashed) ctx.setLineDash([5, 4]);
        ctx.strokeStyle = COLOR.featureStroke;
        ctx.lineWidth = 1.2;
        ctx.stroke();
        ctx.setLineDash([]);
        if (label) {
            ctx.font = "10px 'Raleway', sans-serif";
            ctx.fillStyle = COLOR.featureText;
            ctx.textAlign = "center";
            const cx = obj.x + obj.w / 2;
            const cy = obj.y + obj.h / 2;
            ctx.fillText(label, cx, sub ? cy - 4 : cy + 4);
            if (sub) {
                ctx.font = "9px 'Raleway', sans-serif";
                ctx.fillText(sub, cx, cy + 10);
            }
        }
    }

    function draw() {
        const layout = layouts[currentLayout];
        if (!layout) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Background
        ctx.fillStyle = COLOR.bg;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const { room, danceFloor, honorTable, podium, stage, entrance, bar, djBooth, tables, label } = layout;

        // Room
        roundRect(room.x, room.y, room.w, room.h, 4);
        ctx.fillStyle = COLOR.room;
        ctx.fill();
        ctx.strokeStyle = COLOR.roomStroke;
        ctx.lineWidth = 2;
        ctx.stroke();

        // Room label
        ctx.font = "bold 12px 'Cinzel', serif";
        ctx.fillStyle = COLOR.label;
        ctx.textAlign = "center";
        ctx.fillText(label.toUpperCase(), room.x + room.w / 2, room.y + 22);

        // Honor table
        if (honorTable) {
            roundRect(honorTable.x, honorTable.y, honorTable.w, honorTable.h, 2);
            drawFeature(honorTable, "Honor Table", null);
        }

        // Podium
        if (podium) {
            roundRect(podium.x, podium.y, podium.w, podium.h, 2);
            drawFeature(podium, podium.label, podium.sub);
        }

        // Dance floor
        roundRect(danceFloor.x, danceFloor.y, danceFloor.w, danceFloor.h, 3);
        ctx.fillStyle = COLOR.feature;
        ctx.fill();
        ctx.strokeStyle = COLOR.featureStroke;
        ctx.lineWidth = 1.5;
        ctx.stroke();
        ctx.font = "11px 'Raleway', sans-serif";
        ctx.fillStyle = COLOR.featureText;
        ctx.textAlign = "center";
        ctx.fillText(danceFloor.label, danceFloor.x + danceFloor.w / 2, danceFloor.y + danceFloor.h / 2 - 6);
        ctx.font = "9px 'Raleway', sans-serif";
        ctx.fillText(danceFloor.sub, danceFloor.x + danceFloor.w / 2, danceFloor.y + danceFloor.h / 2 + 10);

        // Bar
        if (bar) {
            roundRect(bar.x, bar.y, bar.w, bar.h, 2);
            ctx.fillStyle = COLOR.feature;
            ctx.fill();
            ctx.strokeStyle = COLOR.featureStroke;
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.save();
            ctx.translate(bar.x + bar.w / 2, bar.y + bar.h / 2);
            ctx.rotate(-Math.PI / 2);
            ctx.font = "9px 'Raleway', sans-serif";
            ctx.fillStyle = COLOR.featureText;
            ctx.textAlign = "center";
            ctx.fillText(bar.label, 0, 4);
            ctx.restore();
        }

        // DJ Booth
        if (djBooth) {
            roundRect(djBooth.x, djBooth.y, djBooth.w, djBooth.h, 2);
            drawFeature(djBooth, djBooth.label, djBooth.sub);
        }

        // Stage
        if (stage) {
            roundRect(stage.x, stage.y, stage.w, stage.h, 2);
            drawFeature(stage, stage.label, stage.sub);
        }

        // Entrance
        if (entrance) {
            roundRect(entrance.x, entrance.y, entrance.w, entrance.h, 2);
            ctx.fillStyle = COLOR.feature;
            ctx.fill();
            if (entrance.dashed) ctx.setLineDash([5, 4]);
            ctx.strokeStyle = COLOR.featureStroke;
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.font = "9px 'Raleway', sans-serif";
            ctx.fillStyle = COLOR.featureText;
            ctx.textAlign = "center";
            ctx.fillText(entrance.label, entrance.x + entrance.w / 2, entrance.y + entrance.h / 2 + 4);
        }

        // Tables
        tables.forEach((table, index) => {
            const num = index + 1;
            const isSelected = !!selectedTables[num];

            ctx.beginPath();
            ctx.arc(table.x, table.y, R, 0, Math.PI * 2);
            ctx.fillStyle = isSelected ? COLOR.selected : COLOR.tableFill;
            ctx.fill();
            ctx.strokeStyle = isSelected ? COLOR.selectedStroke : COLOR.tableStroke;
            ctx.lineWidth = isSelected ? 2 : 1;
            ctx.stroke();

            ctx.font = `${isSelected ? "bold " : ""}10px 'Raleway', sans-serif`;
            ctx.fillStyle = isSelected ? COLOR.selectedText : COLOR.tableText;
            ctx.textAlign = "center";
            ctx.fillText(num, table.x, table.y + 4);
        });

        updateSummary();
    }

    // ─────────────────────────────────────────────
    // CANVAS CLICK
    // ─────────────────────────────────────────────
    canvas.addEventListener("click", (e) => {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = (e.clientX - rect.left) * scaleX;
        const y = (e.clientY - rect.top) * scaleY;

        layouts[currentLayout].tables.forEach((table, index) => {
            const num = index + 1;
            const dx = x - table.x;
            const dy = y - table.y;
            if (Math.sqrt(dx * dx + dy * dy) < R + 5) {
                const card = document.querySelector(`[data-table="${num}"]`);
                if (!card) return;
                const checkbox = card.querySelector(".table-card-checkbox");
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event("change"));
            }
        });
    });

    // ─────────────────────────────────────────────
    // TABLE CARDS
    // ─────────────────────────────────────────────
    const tableCards = document.querySelectorAll(".table-card");
    tableCards.forEach(card => {
        const checkbox = card.querySelector(".table-card-checkbox");
        const input    = card.querySelector(".table-card-guest-input");
        const num      = parseInt(card.dataset.table);

        card.addEventListener("click", (e) => {
            if (e.target.tagName === "INPUT") return;
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event("change"));
        });

        checkbox.addEventListener("change", () => {
            if (checkbox.checked) {
                card.classList.add("selected");
                input.disabled = false;
                input.focus();
                selectedTables[num] = 1;
            } else {
                card.classList.remove("selected");
                input.disabled = true;
                input.value = "";
                delete selectedTables[num];
            }
            draw();
        });

        input.addEventListener("input", () => {
            let val = parseInt(input.value);
            if (!val || val < 1) val = 1;
            if (val > 12) val = 12;
            input.value = val;
            selectedTables[num] = val;
            updateSummary();
        });
    });

    // ─────────────────────────────────────────────
    // SUMMARY
    // ─────────────────────────────────────────────
    function updateSummary() {
        const tableCount = Object.keys(selectedTables).length;
        const guestCount = Object.values(selectedTables).reduce((s, v) => s + v, 0);
        const tc = document.getElementById("tableCount");
        const gc = document.getElementById("guestCount");
        if (tc) tc.innerText = tableCount;
        if (gc) gc.innerText = guestCount;
    }

    // ─────────────────────────────────────────────
    // FORM SUBMIT
    // ─────────────────────────────────────────────
    const form = document.querySelector(".table-form");
    if (form) {
        form.addEventListener("submit", (e) => {
            const selected = Object.keys(selectedTables);
            if (selected.length === 0) {
                e.preventDefault();
                alert("Please select at least one table.");
                return;
            }

            let valid = true;
            selected.forEach(num => {
                const val = selectedTables[num];
                if (!val || val < 1 || val > 12) valid = false;
            });

            if (!valid) {
                e.preventDefault();
                alert("Each selected table must have between 1 and 12 guests.");
                return;
            }

            const input = document.getElementById("selectedTablesInput");
            if (input) input.value = JSON.stringify(selectedTables);
        });
    }

    draw();
});