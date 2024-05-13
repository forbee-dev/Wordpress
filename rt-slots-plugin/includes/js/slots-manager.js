// Getting elements
let buttonNewSlots = document.getElementById("buttonNewSlots");
let buttonProviders = document.getElementById("buttonProviders");
let slotsWrapper = document.getElementById("slotsWrapper");
let providerWrapper = document.getElementById("providerWrapper");

// Adding click event listener to buttonNewSlots
buttonNewSlots.addEventListener("click", function () {
  buttonNewSlots.classList.add("active");
  slotsWrapper.classList.add("active");
  buttonProviders.classList.remove("active");
  providerWrapper.classList.remove("active");
});

// Adding click event listener to buttonProviders
buttonProviders.addEventListener("click", function () {
  buttonProviders.classList.add("active");
  providerWrapper.classList.add("active");
  buttonNewSlots.classList.remove("active");
  slotsWrapper.classList.remove("active");
});


///////SLOTS///////
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
        // True if '1' or 'on', false otherwise
        temporarilyOffline: obj.temporarily_offline == "1" || obj.temporarily_offline === "on",
        // True if '1' or 'on', false otherwise
        temporarilyOfflineMobile: obj.temporarily_offline_mobile == "1" || obj.temporarily_offline_mobile === "on",
      };
    });
  } catch (error) {
    console.error("Error fetching slugs:", error);
  }
}

/**
 * Add event listeners to buttons in the slots table to handle actions
 */
function applyButtonLogic(fetchedSlugs) {
  //console.log("Fetched slugs:", fetchedSlugs);

  // Helper function to check if a slug is in fetchedSlugs
  const slugExists = (slug) => fetchedSlugs.some((obj) => obj.slug === slug.toLowerCase());
  const checkTemporarilyOffline = (slug) => fetchedSlugs.find((obj) => obj.slug === slug.toLowerCase()).temporarilyOffline;
  const checkTemporarilyOfflineMobile = (slug) => fetchedSlugs.find((obj) => obj.slug === slug.toLowerCase()).temporarilyOfflineMobile;

  document.querySelectorAll(".slot-add-button").forEach((button) => {
    if (slugExists(button.dataset.slug)) {
      // If the slug don't match, remove the event listener and hide the button
      button.removeEventListener("click", handleSlotAction);
      button.disabled = true;
      button.style.display = "none";
    } else {
      button.addEventListener("click", function () {
        handleSlotAction(this.dataset.slug, "add");
      });
    }
  });

  document.querySelectorAll(".slot-publish-button").forEach((button) => {
    if (slugExists(button.dataset.slug)) {
      // If the slug don't match, remove the event listener and hide the button
      button.removeEventListener("click", handleSlotAction);
      button.disabled = true;
      button.style.display = "none";
    } else {
      button.addEventListener("click", function () {
        handleSlotAction(this.dataset.slug, "publish");
      });
    }
  });

  document.querySelectorAll(".slot-update-button").forEach((button) => {
    if (!slugExists(button.dataset.slug)) {
      // If the slug don't match, remove the event listener and hide the button
      button.removeEventListener("click", handleSlotAction);
      button.disabled = true;
      button.style.display = "none";
    } else {
      button.addEventListener("click", function () {
        handleSlotAction(this.dataset.slug, "update");
      });
    }
  });

  document.querySelectorAll(".slot-offline-desktop").forEach((button) => {
    if (slugExists(button.dataset.slug)) {
      button.addEventListener("click", function () {
        handleSlotAction(this.dataset.slug, "temporarily_offline");
      });
    if (checkTemporarilyOffline(button.dataset.slug)) {
      button.checked = true;
    } else {
      button.checked = false;
    }
    } else {
      // If the slug don't match, remove the event listener and hide the button
      button.removeEventListener("click", handleSlotAction);
      button.disabled = true;
      button.style.display = "none";
    }
  });

  document.querySelectorAll(".slot-offline-mobile").forEach((button) => {
    if (slugExists(button.dataset.slug)) {
      button.addEventListener("click", function () {
        handleSlotAction(this.dataset.slug, "temporarily_offline_mobile");
      });
      if (checkTemporarilyOfflineMobile(button.dataset.slug)) {
        button.checked = true;
      } else {
        button.checked = false;
      }
    } else {
      // If the slug don't match, remove the event listener and hide the button
      button.removeEventListener("click", handleSlotAction);
      button.disabled = true;
      button.style.display = "none";
    }
  });
};

function handleSlotAction(slug, actionType) {
  const data = {
    action: "slot_action",
    action_type: actionType,
    slug: slug,
    nonce: slots_manager.nonce,
  };
  const params = new URLSearchParams(data);
  fetch(slots_manager.ajax_url, {
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
    .then((text) => alert(text))
    .catch((error) => console.error("Error:", error));
}

///////PROVIDERS///////

async function fetchProviders() {
  try {
    const response = await fetch(`/wp-json/wp/v2/casino_software?per_page=100`); 
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const totalPages = response.headers.get("X-WP-TotalPages");
    let allProviders = await response.json();

    for (let page = 2; page <= totalPages; page++) {
      const response = await fetch(`/wp-json/wp/v2/casino_software?per_page=100&page=${page}`); 
      const providers = await response.json();
      allProviders = allProviders.concat(providers);
    }

    return allProviders.map((provider) => provider.slug);
  } catch (error) {
    console.error("Error fetching providers:", error);
  }
}

/**
 * Add event listeners to buttons in the providers table to handle actions
 */
function applyButtonLogicProviders(fetchedProviders) {
  document.querySelectorAll(".provider-add-button").forEach((button) => {
    if (fetchedProviders.map((slug) => slug.toLowerCase()).includes(button.dataset.slug.toLowerCase())) {
      // If the slug don't match, remove the event listener and hide the button
      button.removeEventListener("click", handleProviderAction);
      button.disabled = true;
      button.style.display = "none";
    } else {
      button.addEventListener("click", function () {
        handleProviderAction(this.dataset.id, "add");
        button.style.display = "none";
      });
    }
  });

  document.querySelectorAll(".provider-update-button").forEach((button) => {
    button.addEventListener("click", function () {
      handleProviderAction(this.dataset.id, "update");
    });
  });
}

function handleProviderAction(id, actionType) {
  const data = {
    action: "provider_action",
    action_type: actionType,
    id: id,
    nonce: slots_manager.nonce,
  };
  const params = new URLSearchParams(data);
  fetch(slots_manager.ajax_url, {
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
    .then((text) => alert(text))
    .catch((error) => console.error("Error:", error));
}