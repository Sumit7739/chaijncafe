function setActiveFilter(elem) {
    // Remove 'active' from all filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    // Add 'active' to the clicked button
    elem.classList.add('active');

    // OPTIONAL: Fade out, then fade in the stats container
    const statsContainer = document.getElementById('statsContainer');
    statsContainer.style.opacity = 0;

    setTimeout(() => {
        // Here you could also load new data dynamically based on the filter
        statsContainer.style.opacity = 1;
    }, 300);
}
document.addEventListener("DOMContentLoaded", function () {
    const filterBtns = document.querySelectorAll(".filter-btn");
    const filters = document.querySelector(".filters");

    // Create slider element
    const slider = document.createElement("div");
    slider.classList.add("slider");
    filters.appendChild(slider);

    function moveSlider(selectedBtn) {
        const btnIndex = Array.from(filterBtns).indexOf(selectedBtn);
        const btnWidth = selectedBtn.offsetWidth;

        slider.style.width = `${btnWidth}px`;
        slider.style.left = `${selectedBtn.offsetLeft}px`;
    }

    // Set initial position
    const activeBtn = document.querySelector(".filter-btn.active") || filterBtns[0];
    moveSlider(activeBtn);

    filterBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.querySelector(".filter-btn.active")?.classList.remove("active");
            this.classList.add("active");
            moveSlider(this);
        });
    });

    // Recalculate on window resize (if needed)
    window.addEventListener("resize", () => moveSlider(document.querySelector(".filter-btn.active")));
});