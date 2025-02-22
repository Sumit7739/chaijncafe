document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById("amount-input");
    const amountButtons = document.querySelectorAll(".amount-btn");
    const clearButton = document.getElementById("clear-btn");

    let totalAmount = 0;

    amountButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const amount = parseInt(this.getAttribute("data-amount"));
            totalAmount += amount;
            amountInput.value = totalAmount;
        });
    });

    clearButton.addEventListener("click", function () {
        totalAmount = 0;
        amountInput.value = "";
    });
});