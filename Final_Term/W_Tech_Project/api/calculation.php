<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TravelGuide – Travel Cost Calculator</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <a href="index.html" class="nav-logo">
      <span class="logo-icon">TG</span>
      <span>TravelGuide</span>
    </a>

    <ul class="nav-links">
      <li><a href="index.html">Home</a></li>
      <li><a href="explore.html">Explore</a></li>
      <li><a href="calculator.html" class="active">Calculator</a></li>
    </ul>

    <div class="nav-actions">
      <div id="navGuestActions">
        <a href="login.html" class="btn-text">Login</a>
        <a href="register.html" class="btn-primary">Register</a>
      </div>
      <div id="navUserDisplay" class="nav-user" style="display:none;">
        <span id="navUserName"></span>
        <span id="navUserRole" class="user-badge-role"></span>
        <button class="btn-outline" onclick="logoutUser()">Logout</button>
      </div>
    </div>
  </nav>

  <!-- CALCULATOR -->
  <main>
    <section class="section light-section">
      <div style="max-width:1050px; margin:0 auto;">
        <span class="eyebrow">✦ Plan Your Budget</span>
        <h2>Travel Cost Calculator</h2>
        <p class="sub-text">Estimate total trip expenses in seconds with live breakdown.</p>
      </div>

      <div class="calculator-grid">
        <!-- Input Form -->
        <div class="card card-form">
          <div class="form-group">
            <label for="cDest">Destination</label>
            <input type="text" id="cDest" class="form-input" placeholder="e.g. Cappadocia, Turkey" value="Dhaka, Bangladesh"/>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="cTrav">Number of Travelers</label>
              <input type="number" id="cTrav" class="form-input" value="2" min="1"/>
            </div>
            <div class="form-group">
              <label for="cDays">Number of Days</label>
              <input type="number" id="cDays" class="form-input" value="5" min="1"/>
            </div>
          </div>

          <h4 style="margin: 18px 0 12px; font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">
            Costs (USD)
          </h4>

          <div class="form-group">
            <label for="cTrans">🚌 Transportation Cost (total)</label>
            <input type="number" id="cTrans" class="form-input" value="500"/>
          </div>
          <div class="form-group">
            <label for="cAccom">🏨 Accommodation (per night)</label>
            <input type="number" id="cAccom" class="form-input" value="800"/>
          </div>
          <div class="form-group">
            <label for="cFood">🍽️ Food Cost (per person/day)</label>
            <input type="number" id="cFood" class="form-input" value="300"/>
          </div>
          <div class="form-group">
            <label for="cOther">🎒 Other Expenses (total)</label>
            <input type="number" id="cOther" class="form-input" value="100"/>
          </div>

          <button type="button" id="btnCalc" class="btn-primary btn-block" style="margin-top:12px;" onclick="calculateMyBudget()">
            Calculate Total Cost
          </button>
        </div>

        <!-- Result Box -->
        <div class="card card-result">
          <div id="resEmpty" class="result-placeholder">
            <span class="icon">🧮</span>
            <h3>Your Results Appear Here</h3>
            <p>Fill in the trip details and click calculate to see your estimated budget.</p>
          </div>

          <div id="resFilled" class="result-breakdown" style="display:none;">
            <div id="rTotal" class="total-amount">$0</div>
            <span class="total-label">Estimated Total Budget</span>

            <div class="summary-list">
              <div class="summary-item"><span>Destination</span><b id="rDest">—</b></div>
              <div class="summary-item"><span>Travelers</span><b id="rTrav">—</b></div>
              <div class="summary-item"><span>Duration</span><b id="rDays">—</b></div>
              <div class="summary-item"><span>Transportation</span><b id="rTrans">—</b></div>
              <div class="summary-item"><span>Accommodation</span><b id="rAccom">—</b></div>
              <div class="summary-item"><span>Food Total</span><b id="rFood">—</b></div>
              <div class="summary-item"><span>Other</span><b id="rOther">—</b></div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-bottom">
      <p>&copy; 2028 TravelGuide. All rights reserved.</p>
    </div>
  </footer>

  <div id="toast" class="toast"></div>

  <script src="script.js"></script>
  <script>
    function calculateMyBudget() {
      // Get values
      const dest      = document.getElementById('cDest').value.trim() || 'Not specified';
      const travelers = parseInt(document.getElementById('cTrav').value) || 1;
      const days      = parseInt(document.getElementById('cDays').value) || 1;
      const transport = parseFloat(document.getElementById('cTrans').value) || 0;
      const accom     = parseFloat(document.getElementById('cAccom').value) || 0;
      const food      = parseFloat(document.getElementById('cFood').value) || 0;
      const other     = parseFloat(document.getElementById('cOther').value) || 0;

      // Mathematical calculation
      const totalAccom = accom * days;
      const totalFood  = food * travelers * days;
      const grandTotal = transport + totalAccom + totalFood + other;

      // Format currency
      const formatUSD = num => '$' + Number(num).toLocaleString();

      // Render to DOM
      document.getElementById('rTotal').textContent = formatUSD(grandTotal);
      document.getElementById('rDest').textContent  = dest;
      document.getElementById('rTrav').textContent  = `${travelers} person(s)`;
      document.getElementById('rDays').textContent  = `${days} day(s)`;
      document.getElementById('rTrans').textContent = formatUSD(transport);
      document.getElementById('rAccom').textContent = formatUSD(totalAccom);
      document.getElementById('rFood').textContent  = formatUSD(totalFood);
      document.getElementById('rOther').textContent = formatUSD(other);

      // Display results box
      document.getElementById('resEmpty').style.display = 'none';
      document.getElementById('resFilled').style.display = 'block';

      // Toast
      const toast = document.getElementById('toast');
      if (toast) {
        toast.textContent = 'Calculated Total: ' + formatUSD(grandTotal);
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
      }

      // Background AJAX to MySQL
      if (typeof sendAjax === 'function') {
        sendAjax({
          url: 'api/calculator.php',
          method: 'POST',
          data: {
            destination: dest,
            travelers: travelers,
            days: days,
            transport: transport,
            accommodation: accom,
            food: food,
            other: other,
            email: (typeof Cookies !== 'undefined' ? Cookies.get('tg_email') : '') || ''
          }
        });
      }
    }
  </script>
</body>
</html>