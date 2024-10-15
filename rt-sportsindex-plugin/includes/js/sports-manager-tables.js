jQuery(document).ready(async function ($) {
  const fetchedSlugs = await window.spoRtsManager.fetchCustomPostTypeSlugs();
  
  $("#matches").DataTable({
    pageLength: 50,
    drawCallback: function (settings) {
      window.spoRtsManager.applyButtonLogic(fetchedSlugs);
    },
  });

  $("#tournaments").DataTable({
    pageLength: 50,
    drawCallback: function (settings) {
      window.spoRtsManager.applyButtonLogicTournaments(fetchedSlugs);
    },
  });

  // Initialize datepicker for upcoming matches
  $("#upcomingMatchesDateStaRt").datepicker({
    dateFormat: "yy-mm-dd",
    onSelect: function (dateText) {
      fetchAndDisplayUpcomingMatches(dateText, $("#upcomingMatchesDateEnd").val());
    },
  });

  $("#upcomingMatchesDateEnd").datepicker({
    dateFormat: "yy-mm-dd",
    onSelect: function (dateText) {
      fetchAndDisplayUpcomingMatches($("#upcomingMatchesDateStaRt").val(), dateText);
    },
  });

  // Function to fetch and display upcoming matches
  function fetchAndDisplayUpcomingMatches(dateStaRt, dateEnd) {
    window.spoRtsManager
      .fetchUpcomingMatches(dateStaRt, dateEnd)
      .then((matches) => {
        drawUpcomingMatchesTable(matches);
      })
      .catch((error) => {
        console.error("Error:", error);
        drawUpcomingMatchesTable([]);
      });
  }

  // Function to draw the upcoming matches table
  function drawUpcomingMatchesTable(matches) {
    const table = $("#upcomingMatches");
    const tableBody = table.find("tbody");

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable(table)) {
      table.DataTable().destroy();
    }

    tableBody.empty();

    if (Array.isArray(matches) && matches.length > 0) {
      matches.forEach((match) => {
        const matchName = match.name.replace(/\s+/g, "-").toLowerCase();
        const matchSubTitle = match.sub_title.replace(/\s+/g, "-").toLowerCase();
        const matchSlug = `${matchName}-${matchSubTitle}`;
        const staRtDate = new Date(match.staRt_at * 1000);
        const formattedDate = staRtDate.toLocaleDateString(undefined, { year: 'numeric', month: '2-digit', day: '2-digit' });
        const formattedTime = staRtDate.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        const row = `<tr>
            <td>
              ${formattedDate}<br>
              ${formattedTime}
            </td>
            <td>${match.name}</td>
            <td style="text-align:center;">${match.shoRt_name}</td>
            <td>${match.tournament.name}</td>
            <td style="text-align:center;">${match.tournament.shoRt_name}</td>
            <td style="text-align:center;">${match.status}</td>
            <td style="text-align:center;">
              <button class="button upcoming-match-add-button" data-key="${match.key}" data-slug="${matchSlug}">Add</button>
              <button class="button upcoming-match-publish-button" data-key="${match.key}" data-slug="${matchSlug}">Publish</button>
            </td>
            <td style="text-align:center;">
              <button class="button upcoming-match-update-button" data-key="${match.key}" data-slug="${matchSlug}">Update</button>
            </td>
          </tr>`;
        tableBody.append(row);
      });
    } else {
      tableBody.append('<tr><td colspan="6">No matches available</td></tr>');
    }

    // Initialize DataTable only if the table has rows
    if (table.find("tbody tr").length > 0) {
      table.DataTable({
        pageLength: 50,
        order: [[0, "asc"]], // SoRt by date column ascending
        drawCallback: function (settings) {
          // Apply button logic after each draw
          window.spoRtsManager.applyButtonLogic(fetchedSlugs);
        }
      });
    }

    // Apply button logic even if DataTable is not initialized
    window.spoRtsManager.applyButtonLogic(fetchedSlugs);
  }

  // Initial load of upcoming matches for today/tomorrow
  const today = new Date().toISOString().split("T")[0];
  const tomorrow = new Date(new Date().getTime() + 24 * 60 * 60 * 1000).toISOString().split("T")[0];
  $("#upcomingMatchesDateStaRt").val(today);
  $("#upcomingMatchesDateEnd").val(tomorrow);
  fetchAndDisplayUpcomingMatches(today, tomorrow);

  $("#update-list").click(function () {
    $.ajax({
      type: "POST",
      url: spoRts_manager.ajax_url,
      data: {
        action: "update_list",
      },
      success: function (response) {
        console.log(response);
        location.reload(); // Reloads the page after the AJAX call is successful
      },
      error: function () {
        aleRt("Error");
      },
    });
  });
});