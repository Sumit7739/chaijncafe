function toggleOverlay() {
    const overlay = document.getElementById("overlay");
    overlay.classList.toggle("active");
}

const bellIcon = document.getElementById("bell-icon");
const notificationPopup = document.getElementById("notificationPopup");

bellIcon.addEventListener("click", function (event) {
    notificationPopup.classList.toggle("active");

    // Prevent event from closing when clicking inside popup
    event.stopPropagation();
});

// Close popup when clicking outside
document.addEventListener("click", function (event) {
    if (!bellIcon.contains(event.target) && !notificationPopup.contains(event.target)) {
        notificationPopup.classList.remove("active");
    }
});

document.getElementById("qrButton").addEventListener("click", function () {
    document.getElementById("qrPopup").classList.add("active");
});

document.getElementById("closeQrPopup").addEventListener("click", function () {
    document.getElementById("qrPopup").classList.remove("active");
});
