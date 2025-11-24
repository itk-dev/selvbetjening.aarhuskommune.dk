import React from "react";
import * as PropTypes from "prop-types";
import "./calendar-cell-info-button.scss";

/**
 * Calendar cell information button component.
 *
 * @param {object} props Props.
 * @param {string} props.resource Resource object.
 * @param {Function} props.onClickEvent Resource click event
 * @returns {JSX.Element} Calendar cell information button component.
 */
function CalendarCellInfoButton({ resource, onClickEvent }) {
  return (
    <span className="calendar-cell-info">
      <button
        className="calendar-cell-info-button"
        type="button"
        onClick={() => {
          onClickEvent(resource);
        }}
      >
        {resource.title}
      </button>
    </span>
  );
}

CalendarCellInfoButton.propTypes = {
  resource: PropTypes.shape({
    title: PropTypes.string.isRequired,
  }).isRequired,
  onClickEvent: PropTypes.func.isRequired,
};

export default CalendarCellInfoButton;
