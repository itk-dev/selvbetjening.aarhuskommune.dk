import React, { useEffect, useRef, useState } from "react";
import ReactDOMServer from "react-dom/server";
import dayjs from "dayjs";
import * as PropTypes from "prop-types";
import FullCalendar from "@fullcalendar/react";
import resourceTimegrid from "@fullcalendar/resource-timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import daLocale from "@fullcalendar/core/locales/da";
import { handleBusyIntervals, handleResources } from "../util/calendar-utils";
import CalendarHeader from "./calendar-header";
import Api from "../util/api";
import CalendarSelectionBox from "./calendar-selection-box";
import { displayError, displaySuccess } from "../util/display-toast";
import LoadingSpinner from "./loading-spinner";
import "./calendar.scss";

/**
 * @param {object} props Props.
 * @param {object} props.config App config.
 * @param {object} props.booking Booking to edit.
 * @param {Function} props.onBookingChanged Callback when booking has changed.
 * @param {Function} props.close Callback to close edit component without action.
 * @returns {JSX.Element} Component.
 */
function UserBookingEdit({ config, booking, onBookingChanged, close }) {
  const [loading, setLoading] = useState(false);
  const [events, setEvents] = useState([]);
  const [resources, setResources] = useState([]);
  const [date, setDate] = useState(new Date());
  const [calendarSelection, setCalendarSelection] = useState(null);
  const calendarRef = useRef();

  useEffect(() => {
    if (booking) {
      setDate(new Date(booking.start));

      Api.fetchResource(config.api_endpoint, booking.resourceMail).then((resource) => {
        setResources([resource]);
      });
    }
  }, [booking]);

  useEffect(() => {
    if (resources.length > 0) {
      Api.fetchEvents(config.api_endpoint, resources, date)
        .then((newEvents) => setEvents(newEvents))
        .catch((err) => {
          displayError("Kunne ikke hente optagede intervaller.", err);
        });
    }
  }, [resources]);

  useEffect(() => {
    if (calendarSelection) {
      calendarRef?.current?.getApi().gotoDate(date);
    }

    if (resources.length > 0) {
      Api.fetchEvents(config.api_endpoint, resources, date).then((newEvents) => setEvents(newEvents));
    }
  }, [date]);

  /**
   * Edit booking.
   *
   * @param {object} bookingEdit Booking to edit.
   * @param {Date} newStart New start date.
   * @param {Date} newEnd New end date.
   */
  const requestDateChange = (bookingEdit, newStart, newEnd) => {
    const newData = {
      id: bookingEdit.exchangeId,
      start: newStart.toISOString(),
      end: newEnd.toISOString(),
    };

    if (bookingEdit?.exchangeId) {
      setLoading(true);

      Api.patchBooking(config.api_endpoint, bookingEdit.exchangeId, newData)
        .then((data) => {
          displaySuccess("Ændring af tidspunkt lykkedes");

          onBookingChanged(bookingEdit.exchangeId, data.start, data.end);
        })
        .catch((err) => {
          displayError("Ændring af tidspunkt fejlede.", err);
        })
        .finally(() => {
          setLoading(false);
        });
    }
  };

  /**
   * Format date string.
   *
   * @param {string} dateString Date string to format.
   * @returns {string} Date formatted as string.
   */
  function getFormattedDateTime(dateString) {
    return dayjs(dateString).format("D/M [kl.] HH:mm");
  }

  /**
   * OnCalenderSelection.
   *
   * @param {object} selection The new selection object.
   * @returns {void} Nothing is returned
   */
  const onCalendarSelection = (selection) => {
    const newSelection = {
      allDay: selection.allDay,
      resourceId: selection.resource.id,
      end: selection.end,
      start: selection.start,
    };

    setCalendarSelection(newSelection);
  };

  useEffect(() => {
    const highlightElement = document.querySelector("div.fc-highlight");

    if (highlightElement !== null && resources?.length > 0) {
      setTimeout(() => {
        document.querySelector("div.fc-highlight").innerHTML = ReactDOMServer.renderToString(
          <CalendarSelectionBox
            calendarSelection={calendarSelection}
            calendarSelectionResourceTitle={resources[0].resourceName ?? ""}
            calendarSelectionResourceId={resources[0].id}
            actionText="Anmod om ændring af tidspunkt"
          />,
        );

        document.getElementById("calendar-selection-choice-confirm").addEventListener("mousedown", (e) => {
          e.stopPropagation();

          requestDateChange(booking, calendarSelection.start, calendarSelection.end);
        });

        document.getElementById("calendar-selection-container").addEventListener("mousedown", (e) => {
          e.stopPropagation();
        });

        document.getElementById("calendar-selection-close").addEventListener("mousedown", (e) => {
          e.stopPropagation();

          calendarRef.current.getApi().unselect();

          setCalendarSelection(null);
        });
      }, 1);
    }
  }, [calendarSelection, events]);

  const getBusyIntervals = (busyIntervals) => {
    return busyIntervals.map((value) => {
      const interval = handleBusyIntervals(value);

      if (interval.start === booking.start && interval.end === booking.end) {
        interval.display = "background";

        interval.title = "Din booking";

        interval.backgroundColor = "#33bd33";
      }

      return interval;
    });
  };

  const getSelectOverlap = (event) => {
    // eslint-disable-next-line no-underscore-dangle
    return event?._def?.ui?.display === "background";
  };

  return (
    <div className="main-container">
      {loading && <LoadingSpinner />}
      {!loading && (
        <div className="Calendar no-gutter col-md-12">
          <h2>Vælg nyt tidspunkt for booking</h2>
          <div className="row">
            <div className="col small-padding" style={{ width: "100%" }}>
              <div style={{ margin: "1em 0" }}>
                <div>
                  <strong>Resource: </strong>
                  {booking.resourceDisplayName}
                </div>
                <div>
                  <strong>Titel på booking: </strong>
                  {booking.title}
                </div>
                <div>
                  <strong>Nuværende valg: </strong>
                  {getFormattedDateTime(booking.start)} - {getFormattedDateTime(booking.end)}
                </div>
                <div>
                  {calendarSelection && (
                    <>
                      <strong>Nyt valg: </strong>
                      {getFormattedDateTime(calendarSelection.start)} - {getFormattedDateTime(calendarSelection.end)}
                    </>
                  )}
                </div>
              </div>

              <CalendarHeader config={config} date={date} setDate={setDate} setIsLoading={() => {}} />
              <FullCalendar
                ref={calendarRef}
                plugins={[
                  resourceTimegrid,
                  interactionPlugin,
                  dayGridPlugin,
                  timeGridPlugin,
                  listPlugin,
                  resourceTimelinePlugin,
                ]}
                titleFormat={{
                  year: "numeric",
                  month: "long",
                  day: "numeric",
                }}
                headerToolbar=""
                initialView="resourceTimelineDay"
                duration="days: 3"
                selectConstraint="businessHours"
                selectMirror
                displayEventTime
                scrollTimeReset={false}
                slotLabelFormat={{
                  hour: "numeric",
                  omitZeroMinute: false,
                }}
                nowIndicator
                navLinks
                height="300px"
                slotDuration="00:15:00"
                allDaySlot={false}
                selectable
                unselectAuto={false}
                schedulerLicenseKey={config.license_key}
                slotMinTime="06:00:00"
                slotMaxTime="24:00:00"
                selectOverlap={getSelectOverlap}
                nextDayThreshold="21:00:00"
                editable={false}
                dayMaxEvents
                locale={daLocale}
                select={onCalendarSelection}
                validRange={{
                  start: date,
                }}
                resourceOrder="resourceId"
                resources={resources.map((value) => handleResources(value, date))}
                events={getBusyIntervals(events)}
              />

              <button type="button" onClick={close} style={{ margin: "1em 0" }}>
                Annullér
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

UserBookingEdit.propTypes = {
  config: PropTypes.shape({
    api_endpoint: PropTypes.string.isRequired,
    license_key: PropTypes.string,
  }).isRequired,
  booking: PropTypes.shape({
    id: PropTypes.string.isRequired,
    resourceDisplayName: PropTypes.string.isRequired,
    title: PropTypes.string.isRequired,
    start: PropTypes.string.isRequired,
    end: PropTypes.string.isRequired,
    resourceMail: PropTypes.string.isRequired,
  }).isRequired,
  onBookingChanged: PropTypes.func.isRequired,
  close: PropTypes.func.isRequired,
};

export default UserBookingEdit;
