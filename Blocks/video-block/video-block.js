document.addEventListener("DOMContentLoaded", function () {
  var videoContainers = document.querySelectorAll(".video-container");
  videoContainers.forEach(function (container) {
    container.addEventListener("click", function () {
      var videoId = this.dataset.videoId;
      var iframe = document.createElement("iframe");
      iframe.setAttribute("src", "https://www.youtube.com/embed/" + videoId + "?autoplay=1");
      iframe.setAttribute("frameborder", "0");
      iframe.setAttribute(
        "allow",
        "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
      );
      iframe.setAttribute("allowfullscreen", "");
      this.innerHTML = "";
      this.appendChild(iframe);
    });
  });
});
