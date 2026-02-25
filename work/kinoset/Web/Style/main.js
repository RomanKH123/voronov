document.addEventListener("DOMContentLoaded", () => {
    const menuItems = document.querySelectorAll(".shap_text");

    menuItems.forEach(item => {
        item.addEventListener("click", () => {
            document.querySelector(".shap_text.active")?.classList.remove("active");
            item.classList.add("active");
        });
    });
});