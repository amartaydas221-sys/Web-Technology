(function () {
  const navToggle = document.querySelector('[data-nav-toggle]');
  const navMenu = document.querySelector('[data-nav-menu]');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => navMenu.classList.toggle('open'));
  }

  const calcForm = document.getElementById('calculatorForm');
  const result = document.getElementById('calculatorResult');
  if (calcForm && result) {
    calcForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = calcForm.querySelector('button[type="submit"]');
      const original = button.textContent;
      button.disabled = true;
      button.textContent = 'Calculating...';
      try {
        const response = await fetch(calcForm.action, {
          method: 'POST',
          body: new FormData(calcForm),
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Calculation failed.');
        result.classList.add('has-result');
        result.innerHTML = `
          <span class="eyebrow orange">Estimated Trip Budget</span>
          <div class="grand-total">$${Number(data.total).toFixed(2)}</div>
          <div class="breakdown">
            <div><span>Transportation</span><strong>$${Number(data.breakdown.transport).toFixed(2)}</strong></div>
            <div><span>Accommodation</span><strong>$${Number(data.breakdown.accommodation).toFixed(2)}</strong></div>
            <div><span>Food</span><strong>$${Number(data.breakdown.food).toFixed(2)}</strong></div>
            <div><span>Other</span><strong>$${Number(data.breakdown.other).toFixed(2)}</strong></div>
          </div>
          <p class="muted">This is an estimate based on the values you entered.</p>`;
      } catch (error) {
        result.classList.remove('has-result');
        result.innerHTML = `<div class="alert alert-error">${escapeHtml(error.message)}</div>`;
      } finally {
        button.disabled = false;
        button.textContent = original;
      }
    });
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
  }
})();
