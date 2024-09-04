// Getting elements

let buttonNewMatches = document.getElementById("buttonNewMatches");
let buttonTournaments = document.getElementById("buttonTournaments");
let matchesWrapper = document.getElementById("matchesWrapper");
let tournamentWrapper = document.getElementById("tournamentWrapper");

// Adding click event listener to buttonNewMatchs
buttonNewMatches.addEventListener("click", function () {
  buttonNewMatches.classList.add("active");
  matchesWrapper.classList.add("active");
  buttonTournaments.classList.remove("active");
  tournamentWrapper.classList.remove("active");
});

// Adding click event listener to buttonTournaments
buttonTournaments.addEventListener("click", function () {
  buttonTournaments.classList.add("active");
  tournamentWrapper.classList.add("active");
  buttonNewMatches.classList.remove("active");
  matchesWrapper.classList.remove("active");
});

///////MATCHES///////
/**
 * Fetches the slugs of custom post type
 *
 */
async function fetchCustomPostTypeSlugs() {
  try {
    let allPosts = [];
    const initialResponse = await fetch(`/wp-json/wp/v2/all-slugs`);
    if (!initialResponse.ok) {
      throw new Error(`HTTP error! Status: ${initialResponse.status}`);
    }

    allPosts = await initialResponse.json();
    
    return allPosts.map((obj) => {
      return {
        slug: obj.slug,
      };
    });    

  } catch (error) {
    console.error("Error fetching slugs:", error);
  }
}

/**
 * Add event listeners to buttons in the Matches table to handle actions
 */
function applyButtonLogic(fetchedSlugs) {
  if (!Array.isArray(fetchedSlugs)) {
    console.error("fetchedSlugs is not an array:", fetchedSlugs);
    return;
  }

  const lowerCaseFetchedSlugs = fetchedSlugs.map((item) => (item && item.slug ? item.slug.toLowerCase() : ""));

  document
    .querySelectorAll(".match-add-button, .match-publish-button, .match-update-button")
    .forEach((button) => {
      const buttonSlug = button.dataset.slug.toLowerCase();      
      const slugExists = lowerCaseFetchedSlugs.includes(buttonSlug);

      // Remove any existing event listeners
      button.removeEventListener("click", handleMatchAction);

      if (button.classList.contains("match-add-button")) {
        if (slugExists) {
          button.disabled = true;
          button.style.display = "none";
        } else {
          button.disabled = false;
          button.style.display = "";
          button.addEventListener("click", function () {
            handleMatchAction(this.dataset.key, "add");
            this.style.display = "none";
          });
        }
      } else if (button.classList.contains("match-publish-button")) {
        if (slugExists) {
          button.disabled = true;
          button.textContent = "Published";
        } else {
          button.disabled = false;
          button.addEventListener("click", function () {
            handleMatchAction(this.dataset.key, "publish");
          });
        }
      } else if (button.classList.contains("match-update-button")) {
        if (slugExists) {
          button.disabled = false;
          button.style.display = "";
          button.addEventListener("click", function () {
            handleMatchAction(this.dataset.key, "update");
          });
        } else {
          button.disabled = true;
          button.style.display = "none";
        }
      }
    });
}

function handleMatchAction(key, actionType) {
  const data = {
    action: "matches_action",
    action_type: actionType,
    key: key,
    nonce: sports_manager.nonce,
  };
  const params = new URLSearchParams(data);
  fetch(sports_manager.ajax_url, {
    method: "POST",
    body: params,
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.text();
    })
    .then((text) => {
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        data = { success: true, data: { message: text } };
      }

      // Display alert to the user
      let message = data.success ? "Success: " : "Error: ";
      message += data.data.message;
      alert(message);
    })
    .catch((error) => {
      console.error("Error details:", error);
      alert("Error: " + error.message);
      console.log("Action type:", actionType);
      console.log("Slug:", slug);
    });
}

///////TORUNAMENTS///////
/**
 * Add event listeners to buttons in the Tournaments table to handle actions
 */
function applyButtonLogicTournaments(fetchedTournaments) {
  if (!Array.isArray(fetchedTournaments)) {
    console.error("fetchedTournaments is not an array:", fetchedTournaments);
    return;
  }

  const lowerCaseFetchedSlugs = fetchedTournaments.map((item) => (item && item.slug ? item.slug.toLowerCase() : ""));

  document
    .querySelectorAll(".tournament-add-button, .tournament-publish-button, .tournament-update-button")
    .forEach((button) => {
      const buttonSlug = button.dataset.slug.toLowerCase();
      const slugExists = lowerCaseFetchedSlugs.includes(buttonSlug);

      // Remove any existing event listeners
      button.removeEventListener("click", handleTournamentAction);

      if (button.classList.contains("tournament-add-button")) {
        if (slugExists) {
          button.disabled = true;
          button.style.display = "none";
        } else {
          button.disabled = false;
          button.style.display = "";
          button.addEventListener("click", function () {
            handleTournamentAction(this.dataset.id, "add");
            this.style.display = "none";
          });
        }
      } else if (button.classList.contains("tournament-publish-button")) {
        if (slugExists) {
          button.disabled = true;
          button.textContent = "Published";
        } else {
          button.disabled = false;
          button.addEventListener("click", function () {
            handleTournamentAction(this.dataset.id, "publish");
          });
        }
      } else if (button.classList.contains("tournament-update-button")) {
        if (slugExists) {
          button.disabled = false;
          button.style.display = "";
          button.addEventListener("click", function () {
            handleTournamentAction(this.dataset.id, "update");
          });
        } else {
          button.disabled = true;
          button.style.display = "none";
        }
      }
    });
}

function handleTournamentAction(id, actionType) {
  const data = {
    action: "tournament_action",
    action_type: actionType,
    id: id,
    nonce: sports_manager.nonce,
  };
  const params = new URLSearchParams(data);
  fetch(sports_manager.ajax_url, {
    method: "POST",
    body: params,
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text();
    })
    .then((text) => {
      //console.log("Raw response:", text);
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        data = { success: true, data: { message: text } };
      }
      // Display alert to the user
      let message = data.success ? "Success: " : "Error: ";
      message += data.data.message;
      alert(message);
    })
    .catch((error) => {
      console.error("Error details:", error);
      alert("Error: " + error.message);
      console.log("Action type:", actionType);
      console.log("ID:", id);
    });
}
