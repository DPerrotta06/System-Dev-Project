document.addEventListener("DOMContentLoaded", () => {
const canvas = document.getElementById("floorCanvas");
const ctx = canvas.getContext("2d");
let selectedTables = {}; 
let currentLayout = document.body.dataset.hall || "grand_salon";
// Layout dimensions
const layouts = {
    grand_salon: [
        { x: 120, y: 100 }, { x: 220, y: 100 }, { x: 320, y: 100 },
        { x: 120, y: 200 }, { x: 220, y: 200 }, { x: 320, y: 200 },
        { x: 170, y: 300 }, { x: 270, y: 300 }
    ],
    royal: [
        { x: 80, y: 80 }, { x: 160, y: 80 }, { x: 240, y: 80 }, { x: 320, y: 80 },
        { x: 80, y: 160 }, { x: 160, y: 160 }, { x: 240, y: 160 }, { x: 320, y: 160 },
        { x: 80, y: 240 }, { x: 160, y: 240 }, { x: 240, y: 240 }, { x: 320, y: 240 }
    ]
};
const tableCards = document.querySelectorAll(".table-card");

//draw canvas
function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    layouts[currentLayout].forEach((table, index) => {
        const tableNumber = index + 1;
        ctx.beginPath();
        if (selectedTables[tableNumber]) {
            ctx.fillStyle = "lightgreen";
            ctx.arc(table.x, table.y, 20, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.strokeStyle = "black";
        ctx.arc(table.x, table.y, 20, 0, Math.PI * 2);
        ctx.stroke();
        ctx.fillStyle = "black";
        ctx.fillText(tableNumber, table.x - 5, table.y + 5);
    });
    updateSummary();
}

//Sync grid with canvas
canvas.addEventListener("click", (e) => {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    layouts[currentLayout].forEach((table, index) => {
        const tableNumber = index + 1;
        const dx = x - table.x;
        const dy = y - table.y;
        if (Math.sqrt(dx * dx + dy * dy) < 20) {
            const card = document.querySelector(`[data-table="${tableNumber}"]`);
            const checkbox = card.querySelector(".table-checkbox");
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event("change"));
        }
    });
});

//Grid logic
tableCards.forEach(card => {
    const checkbox = card.querySelector(".table-checkbox");
    const input = card.querySelector(".guest-input");
    const tableNumber = parseInt(card.dataset.table);
    // Clicking card toggles
    card.addEventListener("click", (e) => {
        if (e.target.tagName === "INPUT") return;
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event("change"));
    });
    // Checkbox change
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

    // Guest input
    input.addEventListener("input", () => {
        let val = parseInt(input.value);
        if (!val || val < 1) val = 1;
        if (val > 12) val = 12;
        input.value = val;
        selectedTables[tableNumber] = val;
        updateSummary();
    });
});

//Update the summary at the top
function updateSummary() {
    const tableCount = Object.keys(selectedTables).length;
    const guestCount = Object.values(selectedTables)
        .reduce((sum, val) => sum + val, 0);
    document.getElementById("tableCount").innerText = tableCount + " Tables Selected";
    document.getElementById("guestCount").innerText = guestCount + " Guests Assigned";
}

//Submit the form
document.getElementById("floorForm").addEventListener("submit", () => {
    document.getElementById("selectedTablesInput").value =
        JSON.stringify(selectedTables);
});

// Initial render of the canvas
draw();
});