<script>
    const symbols = {
        math: ['±', '≥', '≤', '×', '÷', '≈', '≠', '∞', '∑', 'π', '√', '²', '³', '°', '‰', 'µ', 'λ', 'Ω'],
        greek: ['α', 'β', 'γ', 'δ', 'Δ', 'ε', 'θ', 'λ', 'μ', 'Ω', 'ρ', 'σ', 'φ', 'ψ', 'ω'],
        misc: ['™', '®', '©', '✓', '✗', '→', '←', '↑', '↓', '↔', '▲', '▼', '◄', '►', '■', '•', '★']
    };

    function openSymbolModal() {
        populateSymbolGrids();
        const modal = new bootstrap.Modal(document.getElementById('symbolModal'));
        modal.show();
    }

    function populateSymbolGrids() {
        const categories = ['math', 'greek', 'misc'];
        categories.forEach(cat => {
            const grid = document.querySelector(`#${cat}-sym .symbol-grid`);
            if (grid && grid.children.length === 0) {
                symbols[cat].forEach(sym => {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-outline-light text-dark border p-0 d-flex align-items-center justify-content-center';
                    btn.style.width = '38px';
                    btn.style.height = '38px';
                    btn.style.fontSize = '1.2rem';
                    btn.innerText = sym;
                    btn.onclick = () => insertSymbol(sym);
                    grid.appendChild(btn);
                });
            }
        });
    }

    function insertSymbol(sym) {
        // Restore selection if lost
        if (savedTextSelection) {
            const currentSel = window.getSelection();
            currentSel.removeAllRanges();
            currentSel.addRange(savedTextSelection);
        }

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            range.deleteContents();
            const textNode = document.createTextNode(sym);
            range.insertNode(textNode);
            
            // Move cursor after the symbol
            range.setStartAfter(textNode);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
            
            // Trigger input event to update model
            const ce = textNode.parentElement.closest('[contenteditable="true"]');
            if (ce) {
                ce.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } else {
            // Fallback for when no specific range is selected
            document.execCommand('insertText', false, sym);
        }

        // Close modal after insertion
        const modalEl = document.getElementById('symbolModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
</script>

<style>
    .symbol-grid button:hover {
        background-color: #e8f0fe !important;
        border-color: #1a73e8 !important;
        color: #1a73e8 !important;
        z-index: 2;
    }
    
    #symbolModal .nav-link {
        color: #5f6368;
    }
    
    #symbolModal .nav-link.active {
        color: #1a73e8;
        border-bottom: 2px solid #1a73e8 !important;
        background: transparent !important;
    }
</style>
