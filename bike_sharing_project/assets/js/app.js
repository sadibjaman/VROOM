
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Initialize booking form features
    initBookingForm();
});

// Initialize booking form features
function initBookingForm() {
    const originInput = document.getElementById('origin');
    const destinationInput = document.getElementById('destination');
    const rideTypeSelect = document.getElementById('rideType');
    const suggestionsLink = document.getElementById('routeSuggestionsLink');
    
    if (!originInput || !destinationInput || !suggestionsLink) return;
    
    function updateSuggestionsLink() {
        const origin = encodeURIComponent(originInput.value.trim());
        const destination = encodeURIComponent(destinationInput.value.trim());
        const hasQuery = origin || destination;
        suggestionsLink.href = `/bike_sharing_project/rides/route_suggestions.php?origin=${origin}&destination=${destination}`;
        suggestionsLink.style.display = hasQuery ? 'inline-block' : 'none';
    }
    
    function debounce(fn, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), wait);
        };
    }
    
    const debouncedUpdate = debounce(() => {
        calculateFareEstimate();
        updateSuggestionsLink();
    }, 200);
    
    originInput.addEventListener('input', debouncedUpdate);
    destinationInput.addEventListener('input', debouncedUpdate);
    if (rideTypeSelect) rideTypeSelect.addEventListener('change', debouncedUpdate);
    
    updateSuggestionsLink();
}

// Update ride status
function updateRideStatus(rideId, status) {
    fetch('/bike_sharing_project/rides/ride_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rideId, status })
    })
    .then(response => response.json())
    .then(data => {
        if (!data?.success) {
            alert('Error updating ride status');
            return;
        }

        if (status === 'accepted' && data.route) {
            showRouteInfo(data);
            return; // Don't reload to keep route visible
        }

        if (status === 'completed' && data.fare) {
            alert(`Ride completed. Fare: $${data.fare.adjusted.toFixed(2)} (adj ${Math.round(data.fare.percent * 100)}%)`);
        }

        location.reload();
    })
    .catch(() => alert('Network error updating ride status'));
}

// Show route information
function showRouteInfo(data) {
    const container = document.getElementById('acceptedRouteInfo');
    if (!container) return;
    
    const stepsHtml = (data.suggestedSteps || []).map((s, i) => `<li>${i + 1}. ${s}</li>`).join('');
    container.innerHTML = `
        <div class="dashboard-card">
            <h3>Suggested Route</h3>
            <p><strong>From:</strong> ${escapeHtml(data.route.origin)}<br>
               <strong>To:</strong> ${escapeHtml(data.route.destination)}</p>
            <p><strong>Est. Time:</strong> ${Number(data.route.estimatedTime).toFixed(0)} min<br>
               <strong>Est. Distance:</strong> ${Number(data.route.estimatedDistance).toFixed(1)} km<br>
               <strong>Est. Fare:</strong> $${Number(data.route.estimatedFare).toFixed(2)}</p>
            <ol>${stepsHtml}</ol>
        </div>`;
    container.style.display = 'block';
}

// Calculate fare estimate
function calculateFareEstimate() {
    const origin = document.getElementById('origin')?.value;
    const destination = document.getElementById('destination')?.value;
    const rideType = document.getElementById('rideType')?.value;
    const estimateDiv = document.getElementById('fareEstimate');
    
    if (!origin || !destination || !estimateDiv) return;
    
    const baseRates = { motorbike: 2.5, scooter: 2.0, ebike: 1.5 };
    const baseRate = baseRates[rideType] || 1.5;
    const distance = Math.random() * 18 + 2; // 2 - 20 km
    const timeMinutes = Math.random() * 35 + 10; // 10 - 45 min
    const surge = 0.9 + Math.random() * 0.6; // 0.9x - 1.5x
    const estimate = ((distance * baseRate) + (timeMinutes * 0.1)) * surge;
    
    estimateDiv.innerHTML = `<strong>Estimated Fare: $${estimate.toFixed(2)}</strong><br><small>~${distance.toFixed(1)} km, ~${timeMinutes.toFixed(0)} min${surge > 1.2 ? ' (surge applied)' : ''}</small>`;
    estimateDiv.style.display = 'block';
}

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>\"]/g, function(s) {
        switch (s) {
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            default: return s;
        }
    });
}
