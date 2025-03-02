function toggleInput(action) {
    const addContainer = document.getElementById('add-input-container');
    const redeemContainer = document.getElementById('redeem-input-container');

    if (action === 'add') {
        addContainer.style.display = addContainer.style.display === 'block' ? 'none' : 'block';
        redeemContainer.style.display = 'none';
    } else if (action === 'redeem') {
        redeemContainer.style.display = redeemContainer.style.display === 'block' ? 'none' : 'block';
        addContainer.style.display = 'none';
    }
}

function addPoints() {
    const amount = parseFloat(document.getElementById('add-amount').value);
    if (isNaN(amount) || amount <= 0) {
        alert('Enter a valid amount.');
        return;
    }

    const points = Math.floor(amount * 10); // Conversion logic (e.g., 1 unit = 10 points)
    const totalPointsElement = document.getElementById('total-points');
    totalPointsElement.innerText = parseInt(totalPointsElement.innerText) + points;

    document.getElementById('add-amount').value = '';
    document.getElementById('add-input-container').style.display = 'none';
}

function redeemPoints() {
    const pointsToRedeem = parseInt(document.getElementById('redeem-points').value);
    const totalPointsElement = document.getElementById('total-points');
    let totalPoints = parseInt(totalPointsElement.innerText);

    if (isNaN(pointsToRedeem) || pointsToRedeem <= 0) {
        alert('Enter a valid number of points.');
        return;
    }

    if (pointsToRedeem > totalPoints) {
        alert('Not enough points to redeem.');
        return;
    }

    totalPointsElement.innerText = totalPoints - pointsToRedeem;

    document.getElementById('redeem-points').value = '';
    document.getElementById('redeem-input-container').style.display = 'none';
}