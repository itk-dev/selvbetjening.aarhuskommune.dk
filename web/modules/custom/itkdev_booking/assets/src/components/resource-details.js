import React from "react";
import * as PropTypes from "prop-types";
import DOMPurify from "dompurify";
import parse from "html-react-parser";
import LoadingSpinner from "./loading-spinner";
import getResourceFacilities from "../util/resource-utils";
import "./resource-details.scss";
import { ReactComponent as IconChair } from "../assets/chair.svg";
import { ReactComponent as IconAarhusKommune } from "../assets/aak-logo-1.svg";

/**
 * REsourece details component.
 *
 * @param {object} props Props.
 * @param {object} props.setShowResourceDetails Set show resource details
 * @param {object} props.resource Object of the resource to show
 * @returns {JSX.Element} Component.
 */
function ResourceDetails({ setShowResourceDetails, resource }) {
  const hideResourceView = () => {
    setShowResourceDetails(null);
  };

  const sanitizedDescription = resource.resourceDescription
    ? parse(DOMPurify.sanitize(resource.resourceDescription, {}))
    : "";

  const getFacilitiesList = () => {
    const facilities = getResourceFacilities(resource);

    return (
      <div className="facility-container">
        <div className="facility-item">
          <div className="facility-icon">
            <IconChair />
          </div>
          <span>{resource.capacity} siddepladser</span>
        </div>
        {Object.values(facilities).map((value) => {
          return (
            <div className="facility-item" key={value.title}>
              <div className="facility-icon">{value.icon}</div>
              <span>{value.title}</span>
            </div>
          );
        })}
      </div>
    );
  };

  return (
    <div className={resource !== null ? "fade-in-content resource-container" : "resource-container"}>
      {!resource && <LoadingSpinner />}
      {resource && (
        <div>
          <div className="resource-headline">
            <span>{resource.resourceDisplayName ?? resource.resourceName}</span>
            <button type="button" className="booking-btn-inv" onClick={hideResourceView}>
              Tilbage til listen
            </button>
          </div>
          <div className="resource-details row">
            <div className="image-wrapper col-xs-12 col-md-4">
              <div className="image">
                {!resource?.resourceImage && (<IconAarhusKommune />)}
                {resource?.resourceImage && (
                  <img alt={resource.resourceDisplayName}
                       src={resource.resourceImage}/>)}
              </div>
            </div>
            <div className="facilities col-xs-12 col-md-4">
              <span className="resource-details--title">Faciliteter</span>
              <div>{getFacilitiesList(resource)}</div>
            </div>
            <div className="location col-xs-12 col-md-4">
              <span className="resource-details--title">Lokation</span>
              <div>
                <span>{resource.locationDisplayName ?? resource.location}</span>
              </div>
              <div className="spacer" />
              <div>
                <span>{resource.streetName}</span>
              </div>
              <div>
                <span>{resource.postalCode}</span>
                &nbsp;
                <span>{resource.city}</span>
              </div>
            </div>
          </div>
          {resource.resourceDescription && (
            <div className="resource-description">
              <span>Beskrivelse</span>
              <div>
                <span>{sanitizedDescription}</span>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

ResourceDetails.propTypes = {
  setShowResourceDetails: PropTypes.func.isRequired,
  resource: PropTypes.shape({
    capacity: PropTypes.number.isRequired,
    resourceDisplayName: PropTypes.string.isRequired,
    resourceName: PropTypes.string.isRequired,
    location: PropTypes.string.isRequired,
    locationDisplayName: PropTypes.string,
    streetName: PropTypes.string.isRequired,
    postalCode: PropTypes.number.isRequired,
    city: PropTypes.string.isRequired,
    resourceImage: PropTypes.string,
    resourceDescription: PropTypes.string,
  }).isRequired,
};

export default ResourceDetails;
