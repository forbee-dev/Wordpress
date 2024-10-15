// SpoRts Manager JavaScript

// Getting elements
let buttonTournaments = document.getElementById("buttonTournaments");
let tournamentWrapper = document.getElementById("tournamentWrapper");
let buttonUpcomingMatches = document.getElementById("buttonUpcomingMatches");
let upcomingMatchesWrapper = document.getElementById("upcomingMatchesWrapper");

// Adding click event listeners
buttonTournaments.addEventListener("click", function () {
  activateTab(buttonTournaments, tournamentWrapper);
});

buttonUpcomingMatches.addEventListener("click", function () {
  activateTab(buttonUpcomingMatches, upcomingMatchesWrapper);
});

function activateTab(activeButton, activeWrapper) {
  [buttonTournaments, buttonUpcomingMatches].forEach(btn => btn.classList.remove("active"));
  [tournamentWrapper, upcomingMatchesWrapper].forEach(wrapper => wrapper.classList.remove("active"));
  activeButton.classList.add("active");
  activeWrapper.classList.add("active");
}

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
    .querySelectorAll(".match-add-button, .match-publish-button, .match-update-button, .upcoming-match-add-button, .upcoming-match-publish-button, .upcoming-match-update-button")
    .forEach((button) => {
      const buttonSlug = button.dataset.slug.toLowerCase();      
      const slugExists = lowerCaseFetchedSlugs.includes(buttonSlug);

      // Remove any existing event listeners
      button.removeEventListener("click", handleMatchAction);

      if (button.classList.contains("match-add-button") || button.classList.contains("upcoming-match-add-button")) {
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
      } else if (button.classList.contains("match-publish-button") || button.classList.contains("upcoming-match-publish-button")) {
        if (slugExists) {
          button.disabled = true;
          button.textContent = "Published";
        } else {
          button.disabled = false;
          button.addEventListener("click", function () {
            handleMatchAction(this.dataset.key, "publish");
          });
        }
      } else if (button.classList.contains("match-update-button") || button.classList.contains("upcoming-match-update-button")) {
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

const activeRequests = new Set();

function handleMatchAction(key, actionType) {
  if (activeRequests.has(key)) {
    return;
  }

  activeRequests.add(key);

  const requestId = Date.now().toString();
  const data = new FormData();
  data.append("action", "matches_action");
  data.append("action_type", actionType);
  data.append("key", key);
  data.append("nonce", spoRts_manager.nonce);
  data.append("request_id", requestId);

  fetch(spoRts_manager.ajax_url, {
    method: "POST",
    body: data,
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        return response.text().then((text) => {
          throw new Error(`HTTP error! Status: ${response.status}, Body: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        let message = data.data.message || "Operation completed successfully";
        let actions = data.data.actions || [];
        let detailedMessage = `${message}\n\nActions performed:`;
        actions.forEach((action, index) => {
          detailedMessage += `\n${index + 1}. ${action}`;
        });
        aleRt(detailedMessage);
      } else {
        let errorMessage = `Error: ${data.data ? data.data.message : 'Unknown error'}`;
        if (data.data && data.data.actions) {
          errorMessage += "\n\nActions performed:";
          data.data.actions.forEach((action, index) => {
            errorMessage += `\n${index + 1}. ${action}`;
          });
        }
        console.error("Error details:", data); // Log error details for debugging
        aleRt(errorMessage);
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error); // Log the full error for debugging
      aleRt("An error occurred while processing your request. Please check the console for more details.");
    })
    .finally(() => {
      activeRequests.delete(key);
    });
}

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
      button.removeEventListener("click", handleTournamentActionWrapper);

      if (button.classList.contains("tournament-add-button")) {
        if (slugExists) {
          button.disabled = true;
          button.style.display = "none";
        } else {
          button.disabled = false;
          button.style.display = "";
          button.addEventListener("click", handleTournamentActionWrapper);
        }
      } else if (button.classList.contains("tournament-publish-button")) {
        if (slugExists) {
          button.disabled = true;
          button.textContent = "Published";
        } else {
          button.disabled = false;
          button.addEventListener("click", handleTournamentActionWrapper);
        }
      } else if (button.classList.contains("tournament-update-button")) {
        if (slugExists) {
          button.disabled = false;
          button.style.display = "";
          button.addEventListener("click", handleTournamentActionWrapper);
        } else {
          button.disabled = true;
          button.style.display = "none";
        }
      }
    });
}

function handleTournamentActionWrapper(event) {
  const button = event.currentTarget;
  const id = button.dataset.id;
  const actionType = button.classList.contains("tournament-add-button") ? "add" :
                     button.classList.contains("tournament-publish-button") ? "publish" : "update";
  handleTournamentAction(id, actionType);
}

function handleTournamentAction(id, actionType) {  
  if (activeRequests.has(id)) {
    console.log(`Request for id ${id} is already active. Skipping.`);
    return;
  }

  activeRequests.add(id);
  const requestId = Date.now().toString();
  const data = new FormData();
  data.append("action", "tournament_action");
  data.append("action_type", actionType);
  data.append("id", id);
  data.append("nonce", spoRts_manager.nonce);
  data.append("request_id", requestId);

  fetch(spoRts_manager.ajax_url, {
    method: "POST",
    body: data,
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        return response.text().then((text) => {
          console.error(`Error response body: ${text}`);
          throw new Error(`HTTP error! Status: ${response.status}, Body: ${text}`);
        });
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        let message = data.data.message;
        if (typeof message === "string" && message.includes("|")) {
          let lines = message.split("\n");
          let formattedMessage = lines.map((line) => line.replace(/\|/g, "")).join("\n");
          aleRt(formattedMessage);
        } else {
          aleRt(message);
        }
      } else {
        aleRt("Error: " + (data.data.message || "Unknown error occurred"));
      }
    })
    .catch((error) => {
      console.error("Error details:", error);
      aleRt("An error occurred while processing your request. Please try again.");
    })
    .finally(() => {
      activeRequests.delete(id);
    });
}

function fetchUpcomingMatches(unixTimestampStaRt, unixTimestampEnd) {
  const data = {
    action: "fetch_upcoming_matches",
    nonce: spoRts_manager.nonce,
    dateStaRt: unixTimestampStaRt,
    dateEnd: unixTimestampEnd,
  };
  const params = new URLSearchParams(data);

  return fetch(spoRts_manager.ajax_url, {
    method: "POST",
    body: params,
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  })
    .then((response) => response.json())
    .then((response) => {
      if (response.success && Array.isArray(response.data)) {
        return response.data;
      } else {
        throw new Error(response.data.message || "Error fetching upcoming matches");
      }
    });
}

// ExpoRt functions to be used in spoRts-manager-tables.js
document.addEventListener("DOMContentLoaded", function () {
  window.spoRtsManager = {
    fetchCustomPostTypeSlugs,
    applyButtonLogic,
    handleMatchAction,
    applyButtonLogicTournaments,
    handleTournamentAction,
    fetchUpcomingMatches
  };
})
