import dayjs from "dayjs";

/**
 * Round to nearest 15 minutes.
 *
 * @param {object} date - Date object
 * @returns {object} - Date object representing the current datetime, rounded up to the next half an hour.
 */
function roundToNearest15(date = new Date()) {
  const minutes = 15;
  const ms = 1000 * 60 * minutes;

  return new Date(Math.ceil(date.getTime() / ms) * ms);
}

/**
 * Round business hours to nearest half hour.
 *
 * @param {string} businessStartHour The hour the resource is available from
 * @param {object} currentCalendarDate Datetime object of the current Fullcalendar instance
 * @param {boolean} returnMilliseconds Return value in ms
 * @returns {string} : formatted date to represent the start of when the resource is available from, either direct
 *   resourcedata or the current time rounded up to the next half an hour, depending on which is largest.
 */
function businessHoursOrNearestFifteenMinutes(businessStartHour, currentCalendarDate, returnMilliseconds) {
  const calendarDate = currentCalendarDate.setHours(0, 0, 0, 0);

  let adjustedBusinessHour = businessStartHour;
  let today = new Date();

  today = today.setHours(0, 0, 0, 0);

  const currentClosestHalfAnHourFormatted = `${
    roundToNearest15(new Date()).getHours().toString().length === 1
      ? `0${roundToNearest15(new Date()).getHours()}`
      : roundToNearest15(new Date()).getHours()
  }:${
    roundToNearest15(new Date()).getMinutes().toString().length === 1
      ? `0${roundToNearest15(new Date()).getMinutes()}`
      : roundToNearest15(new Date()).getMinutes()
  }`;

  if (currentClosestHalfAnHourFormatted > adjustedBusinessHour && calendarDate === today) {
    adjustedBusinessHour = currentClosestHalfAnHourFormatted;
  }

  if (returnMilliseconds) {
    const timeParts = adjustedBusinessHour.split(":");

    adjustedBusinessHour = timeParts[0] * (60000 * 60) + timeParts[1] * 60000;
  }

  return adjustedBusinessHour;
}

/**
 * Handle busy intervals.
 *
 * @param {object} value Busy interval.
 * @returns {object} Busy interval formatted for fullcalendar.
 */
export function handleBusyIntervals(value) {
  return {
    resourceId: value.resource,
    start: value.startTime,
    end: value.endTime,
  };
}

/**
 * Handle resources.
 *
 * @param {object} value Resource.
 * @param {object} currentCalendarDate The current calendar date.
 * @returns {object} Resource formatted for fullcalendar.
 */
export function handleResources(value, currentCalendarDate) {
  if (value.location === "") {
    return {};
  }

  // TODO: Add business hours.
  const businessHoursArray = []; // eslint-disable-line no-param-reassign

  // reformatting openHours to fullcalendar-readable format
  value.openHours.forEach((v) => {
    const startTime = dayjs(v.open).format("HH:mm");
    const endTime = dayjs(v.close).format("HH:mm");

    const businessHours = {
      daysOfWeek: [v.weekday === 7 ? 0 : v.weekday], // Sunday is internally defined as day 0, hence day 7 is converted to day 0 here.
      startTime: businessHoursOrNearestFifteenMinutes(startTime, currentCalendarDate, false),
      endTime,
    };

    businessHoursArray.push(businessHours);
  });

  const resource = {
    resourceId: value.id,
    id: value.resourceMail,
    title: value.resourceDisplayName ?? value.resourceName,
    capacity: value.capacity,
    building: value.locationDisplayName ?? value.location,
    description: value.resourcedescription,
    image: "http://placekitten.com/1920/1080",
    monitorEquipment: value.monitorEquipment,
    videoConferenceEquipment: value.videoConferenceEquipment,
    wheelchairAccessible: value.wheelchairAccessible,
    catering: value.catering,
    telecoil: value.telecoil,
    acceptConflict: value.acceptConflict ?? false,
  };

  if (businessHoursArray.length > 0) {
    resource.businessHours = businessHoursArray;
  } else {
    resource.businessHours = {
      startTime: businessHoursOrNearestFifteenMinutes("00:00", currentCalendarDate, false),
      endTime: "24:00",
    };
  }

  return resource;
}

/**
 * GetScrollTime gets the time to horizontally scroll the calendar to on load
 *
 * @param {boolean} getUnmodifiedTimeScroll Whether to return the unmodified timescroll value.
 * @param {boolean} returnDefault Whether to return the default value for timepicker, if no value is selected.
 * @returns {string} A formatted string, containing the time to scroll to, format "xx:00"
 */
export function getScrollTime(getUnmodifiedTimeScroll = false, returnDefault = false) {
  // Checks if the user has manually chosen a preferred time previously. If so, return it.
  let localTimeScroll = localStorage.getItem("setTimeScroll");

  if (localTimeScroll && localTimeScroll !== "auto") {
    if (getUnmodifiedTimeScroll) {
      return `${localTimeScroll}:00`;
    }

    localTimeScroll -= 1;

    return `${localTimeScroll}:00`;
  }
  // If no manually chosen preferred time is chosen, return auto for time select.
  if (returnDefault) {
    return "auto";
  }

  // Calculates the time the calendar should scroll to horizontally when the calendar loads (now - 2 hours)
  const dateTimeNow = new Date();

  dateTimeNow.setHours(dateTimeNow.getHours() - 2);

  return `${dateTimeNow.getHours()}:00`;
}
