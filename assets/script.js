let url = window.location.href;
console.log(url);
url = url.split('/').pop();
console.log(url);

document.querySelectorAll(".nav-links a").forEach(link => {
    let href = link.getAttribute("href");
    console.log(href);

    if(href === url) {
        link.classList.add("active");
    } else {
        link.classList.remove("active");
    }
})
