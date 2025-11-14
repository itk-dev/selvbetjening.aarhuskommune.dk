/**
 * @param {object} obj Object to test.
 * @param {string} propertyName Property name to test for.
 * @returns {boolean} Does the object have the given property?
 */
export function hasOwnProperty(obj, propertyName) {
  return Object.prototype.hasOwnProperty.call(obj, propertyName);
}

/**
 * @param {object} allResources All Resources.
 * @param {object} filterParams Object containing filtered parameters.
 * @returns {Array} Containing resources matching given filters.
 */
export function filterAllResources(allResources, filterParams) {
  return allResources.filter((resource) => {
    if (resource.location === "") {
      return false;
    }

    /*
      0: no match
      1: neutral // no match
      2: match
    */
    let matchingState = 1;

    // Location filter
    if (filterParams["location[]"] && filterParams["location[]"].length !== 0) {
      if (filterParams["location[]"].includes(resource.location)) {
        matchingState = 2;
      } else {
        matchingState = 0;
      }
    }

    // Resource filter
    if (filterParams["resourceMail[]"] && filterParams["resourceMail[]"].length !== 0) {
      if (filterParams["resourceMail[]"].includes(resource.resourceMail)) {
        matchingState = 2;
      } else if (matchingState === 2) {
        // If already matched, state persists, since location and resource does not intertwine.
        matchingState = 2;
      } else {
        matchingState = 0;
      }
    }

    // VideoConference filter
    if (filterParams.videoConferenceEquipment) {
      if (!resource.videoConferenceEquipment && matchingState === 2) {
        // If resource matched before now, this also has to match.
        matchingState = 0;
      }
      if (resource.videoConferenceEquipment && matchingState === 1) {
        // If resource didn't match before now, it's now a match.
        matchingState = 2;
      }
    }

    // MonitorEquipment filter
    if (filterParams.monitorEquipment) {
      if (!resource.monitorEquipment && matchingState === 2) {
        matchingState = 0;
      }
      if (resource.monitorEquipment && matchingState === 1) {
        matchingState = 2;
      }
    }

    // WheelchairAccessible filter
    if (filterParams.wheelchairAccessible) {
      if (!resource.wheelchairAccessible && matchingState === 2) {
        matchingState = 0;
      }
      if (resource.wheelchairAccessible && matchingState === 1) {
        matchingState = 2;
      }
    }

    // Catering filter
    if (filterParams.catering) {
      if (!resource.catering && matchingState === 2) {
        matchingState = 0;
      }
      if (resource.catering && matchingState === 1) {
        matchingState = 2;
      }
    }

    // Telecoil filter
    if (filterParams.telecoil) {
      if (!resource.telecoil && matchingState === 2) {
        matchingState = 0;
      }
      if (resource.telecoil && matchingState === 1) {
        matchingState = 2;
      }
    }

    // Capacity filter (between two values)
    if (filterParams["capacity[between]"] && matchingState !== 0) {
      const rangeArray = filterParams["capacity[between]"].split(",");

      if (resource.capacity >= rangeArray[0] && resource.capacity <= rangeArray[1]) {
        // If capacity is filtered, it should always overrule earlier matches.
        matchingState = 2;
      } else {
        matchingState = 0;
      }
    }

    // Capacity filter (greater than value)
    if (filterParams["capacity[gt]"] && matchingState !== 0) {
      // If capacity is filtered, it should always overrule earlier matches.
      if (resource.capacity >= filterParams["capacity[gt]"]) {
        matchingState = 2;
      } else {
        matchingState = 0;
      }
    }

    // Resource category filter
    if (filterParams.resourceCategory && matchingState !== 0) {
      // Treat all resources that do not have a resource category set, as "Lokale".
      const resCat =
        resource.resourceCategory === null || resource.resourceCategory === "" ? "Lokale" : resource.resourceCategory;

      if (resCat !== filterParams.resourceCategory) {
        matchingState = 0;
      }
    }

    // HasWhitelist filter
    if (filterParams.hasWhitelist) {
      if (resource.hasWhitelist) {
        matchingState = 2;
      } else {
        matchingState = 0;
      }
    }

    if (matchingState > 1) {
      return resource;
    }

    return false;
  });
}

/**
 * @param {Array} array Array of objects to sort.
 * @param {string} propertyName Property name on object to sort by.
 * @returns {Array} The sorted array.
 */
export function sortOptionsBy(array, propertyName) {
  return array.sort((a, b) => {
    if (a[propertyName] && b[propertyName]) {
      const labelA = a[propertyName].toUpperCase(); // ignore upper and lowercase
      const labelB = b[propertyName].toUpperCase(); // ignore upper and lowercase

      if (labelA < labelB) {
        return -1;
      }
      if (labelA > labelB) {
        return 1;
      }
    }

    return 0;
  });
}
