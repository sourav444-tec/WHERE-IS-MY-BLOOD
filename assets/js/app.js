document.addEventListener("DOMContentLoaded", function () {
  setupDismissibleFlash();
  setupTableSearch();
  setupCountUpStats();
  setupRevealAnimations();
  setupBuyBloodCalculator();
});

function setupDismissibleFlash() {
  var flash = document.querySelector(".flash");
  if (!flash) {
    return;
  }

  var button = document.createElement("button");
  button.type = "button";
  button.className = "flash-close";
  button.setAttribute("aria-label", "Close message");
  button.textContent = "x";

  button.addEventListener("click", function () {
    flash.classList.add("flash-hide");
    setTimeout(function () {
      flash.remove();
    }, 220);
  });

  flash.appendChild(button);
}

function setupTableSearch() {
  var searchBoxes = document.querySelectorAll("[data-table-search]");
  searchBoxes.forEach(function (input) {
    input.addEventListener("input", function () {
      var targetId = input.getAttribute("data-table-search");
      if (!targetId) {
        return;
      }

      var table = document.getElementById(targetId);
      if (!table) {
        return;
      }

      var query = input.value.trim().toLowerCase();
      var rows = table.querySelectorAll("tbody tr");
      var visibleCount = 0;

      rows.forEach(function (row) {
        var text = row.textContent ? row.textContent.toLowerCase() : "";
        var matched = query === "" || text.indexOf(query) !== -1;
        row.style.display = matched ? "" : "none";
        if (matched) {
          visibleCount++;
        }
      });

      var counterId = input.getAttribute("data-search-counter");
      if (counterId) {
        var counter = document.getElementById(counterId);
        if (counter) {
          counter.textContent = String(visibleCount);
        }
      }
    });
  });
}

function setupCountUpStats() {
  var values = document.querySelectorAll(".stat-value");
  values.forEach(function (node) {
    var finalValue = parseInt(node.textContent || "0", 10);
    if (isNaN(finalValue)) {
      return;
    }

    var current = 0;
    var steps = 24;
    var increment = Math.max(1, Math.ceil(finalValue / steps));
    node.textContent = "0";

    var timer = setInterval(function () {
      current += increment;
      if (current >= finalValue) {
        node.textContent = String(finalValue);
        clearInterval(timer);
        return;
      }
      node.textContent = String(current);
    }, 22);
  });
}

function setupRevealAnimations() {
  var cards = document.querySelectorAll(".panel, .card");
  if (!("IntersectionObserver" in window)) {
    cards.forEach(function (card) {
      card.classList.add("is-visible");
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries, obs) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        entry.target.classList.add("is-visible");
        obs.unobserve(entry.target);
      });
    },
    { threshold: 0.1 },
  );

  cards.forEach(function (card) {
    card.classList.add("will-reveal");
    observer.observe(card);
  });
}

function setupBuyBloodCalculator() {
  var unitsInput = document.getElementById("units");
  var previewAmount = document.getElementById("order-amount-preview");
  if (!unitsInput || !previewAmount) {
    return;
  }

  var rate = parseInt(
    previewAmount.getAttribute("data-price-per-unit") || "0",
    10,
  );
  if (isNaN(rate) || rate <= 0) {
    return;
  }

  function updateAmount() {
    var units = parseInt(unitsInput.value || "0", 10);
    if (isNaN(units) || units < 1) {
      previewAmount.textContent = "Rs 0";
      return;
    }

    previewAmount.textContent = "Rs " + String(units * rate);
  }

  unitsInput.addEventListener("input", updateAmount);
  updateAmount();
}
