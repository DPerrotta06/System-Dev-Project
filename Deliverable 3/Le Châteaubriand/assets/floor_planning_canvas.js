document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById("floor-canvas");
    const ctx = canvas.getContext("2d");
    let selectedTables = {};
    let currentLayout = document.body.dataset.hall || "grand_salon";

    // ── COLOURS (matching site theme) ──
    const COLOR = {
        bg:           "#0e0c09",
        room:         "#1a1712",
        roomStroke:   "#C9A84C",
        feature:      "#111008",
        featureStroke:"rgba(201,168,76,0.4)",
        featureText:  "rgba(245,240,232,0.35)",
        tableFill:    "#1a1712",
        tableStroke:  "rgba(201,168,76,0.5)",
        tableText:    "rgba(245,240,232,0.7)",
        selected:     "rgba(201,168,76,0.25)",
        selectedStroke:"#C9A84C",
        selectedText: "#C9A84C",
        label:        "rgba(245,240,232,0.55)",
    };

    // ── TABLE RADIUS ──
    const R = 18;

    // ── LAYOUTS ──
    // Royal Hall: 26 tables around perimeter, mirroring the photo
    // Canvas is 800x600
    const layouts = {

        royal: (() => {
            // The room rect on canvas
            const rx = 60, ry = 55, rw = 680, rh = 470;
            // Dance floor
            const dfx = rx + 160, dfy = ry + 110, dfw = 360, dfh = 230;
            // Honor table (top center)
            const htx = rx + 155, hty = ry + 60, htw = 370, hth = 28;

            const tables = [];

            // LEFT COLUMN (x ≈ rx+55, y spread down)
            const leftX = rx + 52;
            [ry+80, ry+150, ry+220, ry+290, ry+360].forEach(y =>
                tables.push({ x: leftX, y })
            );

            // RIGHT COLUMN (x ≈ rx+rw-55)
            const rightX = rx + rw - 52;
            [ry+80, ry+150, ry+220, ry+290, ry+360].forEach(y =>
                tables.push({ x: rightX, y })
            );

            // TOP ROW (between honor table and left/right)
            tables.push({ x: rx + 110, y: ry + 47 });
            tables.push({ x: rx + rw - 110, y: ry + 47 });

            // BOTTOM ROW (above stage / entrance)
            const bottomY = ry + rh - 48;
            [rx+110, rx+200, rx+310, rx+420, rx+530, rx+rw-110].forEach(x =>
                tables.push({ x, y: bottomY })
            );

            // INNER LEFT (beside dance floor)
            tables.push({ x: dfx - 52, y: dfy + 40 });
            tables.push({ x: dfx - 52, y: dfy + 110 });
            tables.push({ x: dfx - 52, y: dfy + 180 });

            // INNER RIGHT (beside dance floor)
            tables.push({ x: dfx + dfw + 52, y: dfy + 40 });
            tables.push({ x: dfx + dfw + 52, y: dfy + 110 });
            tables.push({ x: dfx + dfw + 52, y: dfy + 180 });

            return {
                tables,
                room: { x: rx, y: ry, w: rw, h: rh },
                danceFloor: { x: dfx, y: dfy, w: dfw, h: dfh },
                honorTable: { x: htx, y: hty, w: htw, h: hth },
                stage: { x: rx + rw/2 + 20, y: ry + rh - 10, w: 130, h: 55 },
                entrance: { x: rx + rw/2 - 120, y: ry + rh - 10, w: 100, h: 40 },
                label: "Royal Hall"
            };
        })(),

        grand_salon: (() => {
            const rx = 60, ry = 55, rw = 680, rh = 470;
            const dfx = rx + 180, dfy = ry + 120, dfw = 320, dfh = 210;
            const tables = [];

            // Left col
            const lx = rx + 52;
            [ry+80, ry+170, ry+260, ry+360].forEach(y => tables.push({ x: lx, y }));

            // Right col
            const rx2 = rx + rw - 52;
            [ry+80, ry+170, ry+260, ry+360].forEach(y => tables.push({ x: rx2, y }));

            // Top row
            [rx+160, rx+280, rx+400, rx+520].forEach(x => tables.push({ x, y: ry+52 }));

            // Bottom row
            [rx+130, rx+250, rx+390, rx+530, rx+rw-130].forEach(x =>
                tables.push({ x, y: ry + rh - 48 })
            );

            // Inner sides
            tables.push({ x: dfx - 52, y: dfy + 60 });
            tables.push({ x: dfx - 52, y: dfy + 150 });
            tables.push({ x: dfx + dfw + 52, y: dfy + 60 });
            tables.push({ x: dfx + dfw + 52, y: dfy + 150 });

            return {
                tables,
                room: { x: rx, y: ry, w: rw, h: rh },
                danceFloor: { x: dfx, y: dfy, w: dfw, h: dfh },
                honorTable: { x: rx + 160, y: ry + 65, w: 360, h: 26 },
                stage: { x: rx + rw/2 - 65, y: ry + rh - 10, w: 130, h: 50 },
                entrance: { x: rx + rw/2 - 230, y: ry + rh - 10, w: 100, h: 36 },
                label: "Grand Salon"
            };
        })(),

        princess: (() => {
            const rx = 100, ry = 80, rw = 600, rh = 400;
            const dfx = rx + 170, dfy = ry + 100, dfw = 260, dfh = 180;
            const tables = [];

            const lx = rx + 45;
            [ry+70, ry+160, ry+260].forEach(y => tables.push({ x: lx, y }));

            const rx2 = rx + rw - 45;
            [ry+70, ry+160, ry+260].forEach(y => tables.push({ x: rx2, y }));

            [rx+130, rx+240, rx+360, rx+470].forEach(x => tables.push({ x, y: ry+45 }));
            [rx+130, rx+240, rx+360, rx+470].forEach(x => tables.push({ x, y: ry+rh-45 }));

            return {
                tables,
                room: { x: rx, y: ry, w: rw, h: rh },
                danceFloor: { x: dfx, y: dfy, w: dfw, h: dfh },
                honorTable: { x: rx + 165, y: ry + 55, w: 270, h: 24 },
                stage: null,
                entrance: { x: rx + rw/2 - 50, y: ry + rh - 8, w: 100, h: 32 },
                label: "Princess"
            };
        })()
    };

    // ── DRAW ──
    function draw() {
        const layout = layouts[currentLayout];
        if (!layout) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Background
        ctx.fillStyle = COLOR.bg;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const { room, danceFloor, honorTable, stage, entrance, tables, label } = layout;

        // Room outline
        ctx.fillStyle = COLOR.room;
        ctx.strokeStyle = COLOR.roomStroke;
        ctx.lineWidth = 2;
        ctx.fillRect(room.x, room.y, room.w, room.h);
        ctx.strokeRect(room.x, room.y, room.w, room.h);

        // Hall label
        ctx.font = "bold 13px 'Cinzel', serif";
        ctx.fillStyle = COLOR.label;
        ctx.textAlign = "center";
        ctx.fillText(label.toUpperCase(), room.x + room.w / 2, room.y + 22);

        // Honor table
        ctx.fillStyle = COLOR.feature;
        ctx.strokeStyle = COLOR.featureStroke;
        ctx.lineWidth = 1;
        ctx.fillRect(honorTable.x, honorTable.y, honorTable.w, honorTable.h);
        ctx.strokeRect(honorTable.x, honorTable.y, honorTable.w, honorTable.h);
        ctx.font = "10px 'Raleway', sans-serif";
        ctx.fillStyle = COLOR.featureText;
        ctx.textAlign = "center";
        ctx.fillText("Honor Table", honorTable.x + honorTable.w / 2, honorTable.y + honorTable.h / 2 + 4);

        // Dance floor
        ctx.fillStyle = COLOR.feature;
        ctx.strokeStyle = COLOR.featureStroke;
        ctx.lineWidth = 1.5;
        ctx.fillRect(danceFloor.x, danceFloor.y, danceFloor.w, danceFloor.h);
        ctx.strokeRect(danceFloor.x, danceFloor.y, danceFloor.w, danceFloor.h);
        ctx.font = "11px 'Raleway', sans-serif";
        ctx.fillStyle = COLOR.featureText;
        ctx.textAlign = "center";
        ctx.fillText("Dance Floor", danceFloor.x + danceFloor.w / 2, danceFloor.y + danceFloor.h / 2 - 6);
        ctx.font = "9px 'Raleway', sans-serif";
        ctx.fillText("(18'×32')", danceFloor.x + danceFloor.w / 2, danceFloor.y + danceFloor.h / 2 + 10);

        // Entrance
        if (entrance) {
            ctx.fillStyle = COLOR.feature;
            ctx.strokeStyle = COLOR.featureStroke;
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 3]);
            ctx.strokeRect(entrance.x, entrance.y, entrance.w, entrance.h);
            ctx.setLineDash([]);
            ctx.fillRect(entrance.x, entrance.y, entrance.w, entrance.h);
            ctx.font = "9px 'Raleway', sans-serif";
            ctx.fillStyle = COLOR.featureText;
            ctx.textAlign = "center";
            ctx.fillText("ENTRANCE", entrance.x + entrance.w / 2, entrance.y + entrance.h / 2 + 4);
        }

        // Stage
        if (stage) {
            ctx.fillStyle = COLOR.feature;
            ctx.strokeStyle = COLOR.featureStroke;
            ctx.lineWidth = 1.5;
            ctx.fillRect(stage.x, stage.y, stage.w, stage.h);
            ctx.strokeRect(stage.x, stage.y, stage.w, stage.h);
            ctx.font = "9px 'Raleway', sans-serif";
            ctx.fillStyle = COLOR.featureText;
            ctx.textAlign = "center";
            ctx.fillText("STAGE", stage.x + stage.w / 2, stage.y + stage.h / 2 - 3);
            ctx.fillText("(10'×18')", stage.x + stage.w / 2, stage.y + stage.h / 2 + 9);
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

    // ── CANVAS CLICK ──
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
            if (Math.sqrt(dx * dx + dy * dy) < R + 4) {
                const card = document.querySelector(`[data-table="${num}"]`);
                if (!card) return;
                const checkbox = card.querySelector(".table-card-checkbox");
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event("change"));
            }
        });
    });

    // ── GRID CARDS ──
    const tableCards = document.querySelectorAll(".table-card");
    tableCards.forEach(card => {
        const checkbox = card.querySelector(".table-card-checkbox");
        const input = card.querySelector(".table-card-guest-input");
        const tableNumber = parseInt(card.dataset.table);

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
                selectedTables[tableNumber] = 1;
            } else {
                card.classList.remove("selected");
                input.disabled = true;
                input.value = "";
                delete selectedTables[tableNumber];
            }
            draw();
        });

        input.addEventListener("input", () => {
            let val = parseInt(input.value);
            if (!val || val < 1) val = 1;
            if (val > 12) val = 12;
            input.value = val;
            selectedTables[tableNumber] = val;
            updateSummary();
        });
    });

    // ── SUMMARY ──
    function updateSummary() {
        const tableCount = Object.keys(selectedTables).length;
        const guestCount = Object.values(selectedTables).reduce((s, v) => s + v, 0);
        const tc = document.getElementById("tableCount");
        const gc = document.getElementById("guestCount");
        if (tc) tc.innerText = tableCount;
        if (gc) gc.innerText = guestCount;
    }

    // ── FORM SUBMIT ──
    const form = document.getElementById(".table-form");
    if (form) {
        form.addEventListener("submit", () => {
            const input = document.getElementById("selectedTablesInput");
            if (input) input.value = JSON.stringify(selectedTables);
        });
    }

    draw();
});


// ── FORM VALIDATION & INTERACTIONS ──
document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector('.table-form');
    const checkboxes = document.querySelectorAll('.table-card-checkbox');

    // Enable/disable inputs
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', function() {
            const input = this.closest('.table-card').querySelector('.table-card-guest-input');

            input.disabled = !this.checked;

            if (!this.checked) {
                input.value = "";
            }
        });
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        const tableCards = document.querySelectorAll('.table-card');

        let valid = false;
        let errorMessage = "";

        tableCards.forEach(card => {
            const checkbox = card.querySelector('.table-card-checkbox');
            const input = card.querySelector('.table-card-guest-input');

            if (checkbox.checked) {
                const value = parseInt(input.value);

                if (!value || value < 1 || value > 12) {
                    errorMessage = "Each selected table must have between 1 and 12 guests.";
                } else {
                    valid = true;
                }
            }
        });

        if (!valid) {
            e.preventDefault();
            alert(errorMessage || "Select at least one table with 1–12 guests.");
        }
    });

});