import React from "react";
import "./list.scss";
import * as PropTypes from "prop-types";
import DOMPurify from "dompurify";
import parse from "html-react-parser";
import getResourceFacilities from "../util/resource-utils";
import { ReactComponent as IconChair } from "../assets/chair.svg";
import { ReactComponent as IconArrow } from "../assets/arrow.svg";
import { ReactComponent as IconAarhusKommune } from "../assets/aak-logo-1.svg";

/**
 * @param {object} props Props.
 * @param {object} props.resources Resources object
 * @param {Function} props.setShowResourceDetails Setter for showResourceDetails resource object
 * @returns {JSX.Element} List of resources
 */
function List({ resources, setShowResourceDetails }) {
  const showResourceView = (event) => {
    const key = event.target.getAttribute("data-key");

    if (resources[key]) {
      setShowResourceDetails(resources[key].resourceMail);
    }
  };

  /**
   * Get facilities list.
   *
   * @param {object} resource Resource object
   * @returns {string} Facilities list.
   */
  const getFacilitiesList = (resource) => {
    const facilities = getResourceFacilities(resource);

    return (
      <div className="facility-container">
        <div className="facility-item">
          <div className="facility-icon">
            <IconChair /> {resource.capacity}
          </div>
        </div>
        {Object.values(facilities).map((value) => {
          return (
            <div className="facility-item" key={value.title}>
              <div className="facility-icon">{value.icon}</div>
            </div>
          );
        })}
      </div>
    );
  };

  return (
    <div>
      {Object.keys(resources).map((key) => {
        const sanitizedDescription = resources[key].resourceDescription
          ? parse(DOMPurify.sanitize(resources[key].resourceDescription, {}))
          : "";

        return (
          <div key={key} className="list-resource">
            <div className="image-wrapper">
              <div className="image">
                {!resources[key]?.resourceImage && (<IconAarhusKommune />)}
                {resources[key]?.resourceImage && (
                  <img alt={resources[key].resourceDisplayName}
                       src={resources[key].resourceImage}/>)}
              </div>
            </div>
            <div className="list-resource-details col-md-10">
              <h2 className="headline">
                {resources[key].resourceDisplayName ?? resources[key].resourceName}
              </h2>
              <div className="location">
                <span className="location-icon">
                  <IconArrow />
                </span>
                <span>
                  {resources[key].locationDisplayName ?? resources[key].location}, {resources[key].streetName}{" "}
                  {resources[key].postalCode} {resources[key].city}
                </span>
              </div>
              {getFacilitiesList(resources[key])}
              <p className="description">{sanitizedDescription}</p>
            </div>
            <div className="list-resource-actions col-md-2">
              <button type="button" className="booking-btn" data-key={key} onClick={showResourceView}>
                Vis resource
              </button>
            </div>
          </div>
        );
      })}
    </div>
  );
}

List.propTypes = {
  resources: PropTypes.arrayOf(
    PropTypes.shape({
      resourceName: PropTypes.string,
      resourceMail: PropTypes.string,
      location: PropTypes.string,
      locationDisplayName: PropTypes.string,
      streetName: PropTypes.string,
      postalCode: PropTypes.number,
      city: PropTypes.string,
      resourceDisplayName: PropTypes.string,
      resourceDescription: PropTypes.string,
      resourceImage: PropTypes.string,
    })
  ).isRequired,
  setShowResourceDetails: PropTypes.func.isRequired,
};

export default List;
