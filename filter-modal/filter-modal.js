// JavaScript for the modal

// DOM elements
const modal = document.getElementById("filterModal");
const steps = document.querySelectorAll(".steps");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const finishBtn = document.getElementById("finishBtn");
const skipBtn = document.getElementById("skipBtn");
const closeBtn = document.getElementById("closeBtn");
const casinoList = document.getElementById("casinoList");
const modalOpenButton = document.querySelectorAll(".open-modal");
const dotsContainer = document.querySelector(".dots");
const tagsList = document.getElementById("tagsList");
const moreBtn = document.querySelector(".see-more-casinos");
const allRows = document.querySelectorAll(".content-bonuses > .main-aRticle");


let currentStep = 1;
let isShowingOriginal = false; // Keep track of the current state of the toplist
// State object to hold shared variables
const sharedState = {
  conditionMet: false
};

/**
 * On page load, check if there are any selected fields in storage.
 */
document.addEventListener("DOMContentLoaded", function () {
  const selectedFields = JSON.parse(localStorage.getItem("selectedFields")) || [];

  // setTimeout is needed to make sure the DOM is ready to implement the filters
  setTimeout(() => {
    filteredByModal();
    showRecommendedCasinos();
    
    // Toggle the filter state twice to refresh the table to the latest changes
    toggleFilter();
    toggleFilter();
  }, 1);
});

if (modal) {
  const modalCta = document.querySelector(".modal-cta");
  /**
   * Shows the step based on the provided step number.
   * @param {number} stepNumber - The number of the step to display.
   */
  function showStep(stepNumber) {
    steps.forEach((step, index) => {
      step.style.display = index + 1 === stepNumber ? "block" : "none";
    });
  }

  /**
   * Toggles the display of the "Previous" button.
   */
  function togglePrevButton() {
    prevBtn.style.display = currentStep === 1 ? "none" : "inline";
  }

  /**
   * Toggles the display of the "Next" and "Finish" buttons.
   * The "Finish" button is only displayed on the last step.
   * The "Next" and "Skip" buttons are only displayed on the first step.
   * If more steps added change the currentStep === 2 to currentStep === 3
   */
  function toggleNextButton() {
    nextBtn.style.display = currentStep === 2 ? "none" : "inline";
    skipBtn.style.display = currentStep === 2 ? "none" : "inline";
    finishBtn.style.display = currentStep === 2 ? "inline" : "none";
  }

  // Initial setup
  showStep(currentStep);
  togglePrevButton();
  toggleNextButton();

  /**
   * Updates the UI based on the current step.
   * This function is called when the user clicks the "Previous" or "Next" buttons.
   * It also updates the active dot.
   */
  function updateStepUI() {
    showStep(currentStep);
    togglePrevButton();
    toggleNextButton();
    updateActiveDot();
  }

  // Event listeners for modal navigation
  prevBtn.addEventListener("click", () => {
    if (currentStep > 1) {
      currentStep--;
      updateStepUI();
    }
  });

  // Event listener for the next button
  nextBtn.addEventListener("click", () => {
    if (currentStep < 3) {
      currentStep++;
      updateStepUI();
    }
  });

  // Event listener for the skip button
  skipBtn.addEventListener("click", () => {
    if (currentStep < 3) {
      currentStep++;
      updateStepUI();
    }
  });

  // Event listener for the finish button
  finishBtn.addEventListener("click", () => {
    const section = document.querySelector(".toplist-block");
    section.scrollIntoView({ behavior: "smooth" });
    isShowingOriginal = false;

    modal.style.display = "none";
    filteredByModal();
    // Toggle the filter state twice to refresh the table
    // this refresh the table results every time there is a change on the modal filter
    toggleFilter();
    toggleFilter();

    if (selectedFields.length === 0) {
      window.resetRows();
    }
  });

  // Event listener for the close button
  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  /**
   * Updates the active dot based on the current step.
   */
  function updateActiveDot() {
    const dots = document.querySelectorAll(".dots li");
    dots.forEach((dot, index) => {
      dot.classList.toggle("active", index + 1 === currentStep);
    });
  }

  // Event listener for the dots
  dotsContainer.addEventListener("click", function (event) {
    const clickedDot = event.target;

    if (clickedDot.tagName === "LI") {
      const dotIndex = Array.from(dotsContainer.children).indexOf(clickedDot);
      currentStep = dotIndex + 1; // Update the current step
      updateStepUI();
    }
  });

  /**
   * Opens the modal.
   * This function is called when the modal is opened automatically or
   * when the user clicks the "Open modal" button.
   */
  function openModal() {
    modal.style.display = "block";
    currentStep = 1;
    showStep(currentStep);
    updateActiveDot();
    updateStepUI();
  }

  /**
   * Check auto-open value and open the modal if it's set to "1".
   */
  const autoOpenValue = modal.getAttribute("data-auto-open");

  if (autoOpenValue === "1") {
    // Automatically open the modal when the page loads
    openModal();
  }

  // Event listener for the modal open button
  modalOpenButton.forEach((button) => {
    button.addEventListener("click", openModal);
  });
} // End of if (modal)

/**
 * Updates the list of selected fields in storage.
 */
function updateStorage() {
  // Update local storage
  localStorage.setItem("selectedFields", JSON.stringify(selectedFields));

}

/**
 * Updates the button's selected state.
 * @param {HTMLElement} button - The button to update.
 */
function updateButtonState(button, isSelected) {
  button.classList.toggle("selected", isSelected);
}

/**
 * Handles the click events for both casinos and tags.
 * The function adds or removes the clicked item from localStorage.
 * It also updates the button state.
 */
function handleButtonClick(event) {
  const clickedElement = event.target;
  const isButton = clickedElement.classList.contains("casino-button");
  const isImageButton =
    clickedElement.tagName === "IMG" &&
    clickedElement.parentElement.classList.contains("casino-button");

  if (isButton || isImageButton) {
    const button = isButton ? clickedElement : clickedElement.parentElement;
    let itemName = button.textContent;

    // Trim leading and trailing whitespace from itemName
    itemName = itemName.trim();

    // Determine whether it's a casino or a tag based on the step
    const step = button.closest(".steps");
    const isCasinoStep = step && step.id === "step1";

    let itemCategory;
    let itemID;
    let itemType;

    if (isCasinoStep) {
      itemCategory = button.getAttribute("data-casino-license"); // Get the category for casinos
      itemID = button.getAttribute("data-casino-id"); // Get the ID for casinos
      itemType = "casino";
    } else {
      // For tags, there's no category, so set it to a default value
      itemCategory = "";
      itemType = "tag";
    }

    // Check if the item is already selected in selectedFields
    const fieldIndex = selectedFields.findIndex(
      (field) =>
        field.name === itemName &&
        field.license === itemCategory &&
        field.type === itemType &&
        field.ID === itemID
    );

    if (fieldIndex > -1) {
      // Field is already selected, remove it
      selectedFields.splice(fieldIndex, 1);
      updateButtonState(button, false); // Update the button state to unselected
    } else {
      // Field is not selected, add it
      selectedFields.push({
        type: itemType,
        name: itemName,
        ID: itemID,
        license: itemCategory,
      });
      updateButtonState(button, true); // Update the button state to selected
    }

    updateStorage();
  }
}

// Logic related to selecting casinos and storing the selection in local storage
const selectedFields = JSON.parse(localStorage.getItem("selectedFields")) || [];

function normalizeHyphens(str) {
  return str.replace(/[\u2013\u2014\u2015\u2212\u002D\uFE58\uFE63\uFF0D]/g, "-");
}

// Initialize button states
document.querySelectorAll(".casino-button").forEach((button) => {
  const originalItemName = normalizeHyphens(button.textContent); // Store the original name
  const itemName = originalItemName.trim(); // Trim the name
  const isSelected = selectedFields.some((field) => normalizeHyphens(field.name).trim() === itemName);
  updateButtonState(button, isSelected);
});

if (modal) {
  // Event listeners for casino list and tagsList (both use the same handler)
  casinoList.addEventListener("click", handleButtonClick);
  tagsList.addEventListener("click", handleButtonClick);
}

/**
 * Function to hide the li elements based on selectedFields in localStorage
 */
let hiddenLiElements = new Set(); // Use Set to keep track of hidden li elements

function hideSelectedLiElements() {
  hiddenLiElements.clear(); // Clear the set at the beginning
  const selectedFields = JSON.parse(localStorage.getItem("selectedFields")) || [];
  // Hide li elements based on IDs in selectedFields
  selectedFields.forEach((field) => {
    const idToHide = field.ID;
    const licenseToHide = field.license;

    // Hide by the ID
    function hideByID(idToHide) {
      const liElement = document.getElementById(idToHide);
      if (liElement) {
        liElement.style.display = "none";
        hiddenLiElements.add(liElement);
      }
    }
    hideByID(idToHide);

    // Hide by data-license attribute
    function hideByLicense(licenseToHide) {
      const liElementsByLicense = document.querySelectorAll(`li[data-license="${licenseToHide}"]`);
      liElementsByLicense.forEach((liElement) => {
        liElement.style.display = "none";
        hiddenLiElements.add(liElement);
      });
    }
    hideByLicense(licenseToHide);
  });
}

// Show by tags
function showLiByTags() {
  selectedFields.forEach((field) => {
    const tagsToShow = selectedFields.filter((field) => field.type === "tag").map((field) => field.name.trim());
   
    function showByTags(tagsToShow) {
      const liElements = document.querySelectorAll(".content-bonuses li");
      let visibleCount = 0;

      if (tagsToShow.length === 0) {
        return 0;
      } 

      liElements.forEach((liElement) => {
        // Skip if the li element is already hidden
        if (hiddenLiElements.has(liElement)) {
          return;
        }

        const elementTags = liElement.getAttribute("data-tags");
        const elementTagArray = elementTags ? elementTags.split(",").map((t) => t.trim()) : [];
        
        if (tagsToShow.some((tag) => elementTagArray.includes(tag))) {
          liElement.style.display = "flex";
          visibleCount++;
        } else {
          liElement.style.display = "none";
          hiddenLiElements.add(liElement);
        }
      });

      return visibleCount;
    }
    let visibleCount = showByTags(tagsToShow);
    let hiddenCount = hiddenLiElements.size;
    window.updateLoadMoreButtonVisibility(visibleCount, hiddenCount);
  });
}

/**
 * Function to show the li elements based on selectedFields in localStorage
 */
function filteredByModal() {
  const selectedFields = JSON.parse(localStorage.getItem("selectedFields")) || [];
  const filteredByModal = document.querySelector(".filteredByModal");
  const backToModal = document.querySelector(".zeroCase");

  setTimeout(() => {
  if (filteredByModal && selectedFields.length > 0 && !isShowingOriginal) {
    filteredByModal.style.display = "flex";
    backToModal.style.display = "none";
    if (sharedState.conditionMet === true) {
      backToModal.style.display = "flex";
      filteredByModal.style.display = "none";
    }
  } else {
    // Hide the filteredByModal div if selectedFields does not exist
    filteredByModal.style.display = "none";
  }
  }, 10);

  // Check for existing UUID or generate a new one
  let uuid = localStorage.getItem("uuid");
  if (!uuid) {
    uuid = uuidv4();
    localStorage.setItem("uuid", uuid);
  }

  // Create a new array with uuid as the first element
  let newArray = [uuid, ...selectedFields];

  sendDataToServer(newArray);
}

/**
 * Function to toggle between showing all li elements and showing filtered list
 */
window.toggleFilter = function toggleFilter() {
  let showOriginalButton = document.getElementById("showOriginal");
  let showFilteredButton = document.getElementById("showFiltered");

  if (isShowingOriginal) {
    // If currently showing the original list, re-apply filters
    hideSelectedLiElements();
    showLiByTags();
    showRecommendedCasinos();
    showOriginalButton.style.display = "block";
    showFilteredButton.style.display = "none";    
  } else {
    hideSelectedLiElements();
    showLiByTags();
    showRecommendedCasinos();
    // If currently showing the filtered list, show all items
    hiddenLiElements.forEach((li) => {
      li.style.display = "flex";
    });
    showOriginalButton.style.display = "none";
    showFilteredButton.style.display = "block";    
  }

  // Toggle the state
  isShowingOriginal = !isShowingOriginal;
}

// Event listener for the Show Original button
  const showOriginalElement = document.getElementById("showOriginal");
  if (showOriginalElement) {
    showOriginalElement.addEventListener("click", toggleFilter);
  }

// Event listener for the Show Filtered button
  const showFilteredElement = document.getElementById("showFiltered");
  if (showFilteredElement) {
    showFilteredElement.addEventListener("click", toggleFilter);
  }

// Event listener for Back to Filtration button
  const backToFilterElement = document.getElementById("backToModal");
  if (backToFilterElement) {
    backToFilterElement.addEventListener("click", openModal);
  }

// Function to send data to the server
function sendDataToServer(data) {
  fetch('/wp-json/filter-modal/v2/store_choices', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      // possibly include authentication headers here
    },
    body: JSON.stringify({ selectedFields: data }),
  })
  .then(response => response.json())
  .then(data => {
    //console.log('Success:', data);
  })
  .catch((error) => {
    console.error('Error:', error);
  });
}

// Function to generate a unique ID (UUID v4)
function uuidv4() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

/**
 * Show recomended casinos in a 0 case scenario
 * 
 */
function showRecommendedCasinos() {
  const currentlyHidden = Array.from(allRows).filter((row) => row.style.display === "none");
  let ids = zeroCaseCasinoData.ids;
  if (allRows.length === currentlyHidden.length) {
      ids.forEach((id) => {
        const liElement = document.getElementById(id);
        if (liElement) {
          liElement.style.display = "flex";
          if (moreBtn) {
            moreBtn.style.display = "none";
          }
        }
      });
      sharedState.conditionMet = true;
  } else {
    sharedState.conditionMet = false;
    const arrayAllRows = Array.from(allRows);
    const rowsPerPage = 25;
    const rowsToShow = arrayAllRows.slice(0, rowsPerPage);

    rowsToShow.forEach((row) => (row.style.display = "flex"));
    hideSelectedLiElements();
    showLiByTags();
  }
}
