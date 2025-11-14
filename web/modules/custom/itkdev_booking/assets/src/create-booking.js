import React, { useEffect, useState } from "react";
import { toast } from "react-toastify";
import * as PropTypes from "prop-types";
import AuthorFields from "./components/author-fields";
import Calendar from "./components/calendar";
import MinimizedDisplay from "./components/minimized-display";
import InfoBox from "./components/info-box";
import ListContainer from "./components/list-container";
import MapWrapper from "./components/map-wrapper";
import MainNavigation from "./components/main-navigation";
import Api from "./util/api";
import UrlValidator from "./util/url-validator";
import { hasOwnProperty, filterAllResources } from "./util/helpers";
import "react-toastify/dist/ReactToastify.css";
import CreateBookingFilters from "./components/create-booking-filters";
import CreateBookingTabs from "./components/create-booking-tabs";
import LoadingSpinner from "./components/loading-spinner";
import { resourceLimit } from "./util/filter-utils";
import ResourceDetails from "./components/resource-details";
import SystemFailureScreen from "./system-failure-screen";

/**
 * CreateBooking component.
 *
 * @param {object} props The props
 * @param {object} props.config App config.
 * @returns {JSX.Element} Component.
 */
function CreateBooking({ config }) {
  // Booking data.
  const [date, setDate] = useState(new Date());
  const [calendarSelection, setCalendarSelection] = useState({});
  const [authorFields, setAuthorFields] = useState({ subject: "", email: "" });
  // Filter parameters, selected in CreateBookingFilters. An object containing
  // structured information about current filtering.
  const [appParams, setAppParams] = useState({});
  const [filterParams, setFilterParams] = useState({});
  const [locationFilter, setLocationFilter] = useState([]);
  const [resourceFilter, setResourceFilter] = useState([]);
  const [resourceCategoryFilter, setResourceCategoryFilter] = useState("Lokale");
  // App configuration and behavior.
  const [displayState, setDisplayState] = useState("maximized");
  const [urlResource, setUrlResource] = useState(null);
  const [validUrlParams, setValidUrlParams] = useState(null);
  const [activeTab, setActiveTab] = useState("calendar");
  const [userHasInteracted, setUserHasInteracted] = useState(false);
  const [showResourceDetails, setShowResourceDetails] = useState(null);
  const [showSystemFailure, setShowSystemFailure] = useState(false);
  // Loaded data.
  const [filteredResources, setFilteredResources] = useState(null);
  const [resources, setResources] = useState(null);
  const [allResources, setAllResources] = useState([]);
  const [userInformation, setUserInformation] = useState(null);
  // Loading
  const [loadingResources, setLoadingResources] = useState(true);
  const [loadingUserInformation, setLoadingUserInformation] = useState(true);
  const [loadingFiltering, setLoadingFiltering] = useState(false);
  const [facilityFilter, setFacilityFilter] = useState([]);

  // Load all resources and current user information.
  useEffect(() => {
    Api.fetchAllResources(config.api_endpoint)
      .then((loadedResources) => {
        setAllResources(loadedResources);
      })
      .catch((fetchAllResourcesError) => {
        toast.error("Der opstod en fejl. Prøv igen senere.", fetchAllResourcesError);

        setShowSystemFailure(true);
      })
      .finally(() => {
        setLoadingResources(false);
      });

    if (!config.step_one) {
      Api.fetchUserInformation(config.api_endpoint)
        .then((retrievedUserInformation) => {
          setUserInformation(retrievedUserInformation);
        })
        .catch((fetchUserInformationError) => {
          toast.error("Der opstod en fejl. Prøv igen senere.", fetchUserInformationError);

          setShowSystemFailure(true);
        })
        .finally(() => {
          setLoadingUserInformation(false);
        });
    } else {
      setLoadingUserInformation(false);
    }
  }, []);

  // @TODO: use app params when implementing new booking tab (lokale, transport etc.)
  useEffect(() => {}, [appParams]);

  // When all resources have been loaded, check if parameters contain
  // selections.
  useEffect(() => {
    // If existing booking data is set in url, start in minimized state.
    if (allResources.length > 0) {
      const currentUrl = new URL(window.location.href);
      const params = Object.fromEntries(currentUrl.searchParams);

      // If a list of urlParameters exist the display can be minimized.
      if (UrlValidator.minimizable(params)) {
        setValidUrlParams(params);

        setDisplayState("minimized");
      }

      const matchingResource = Object.values(allResources).filter((value) => {
        return value.id === parseInt(params.resource, 10);
      })[0];

      if (matchingResource) {
        setUrlResource(matchingResource);
      }

      setFilterParams({
        "location[]": currentUrl.searchParams.getAll("location[]"),
        "resourceMail[]": currentUrl.searchParams.getAll("resourceMail[]"),
      });
    }
  }, [allResources]);

  // Effects to run when urlResource is set. This should only happen once in
  // extension of app initialisation.
  useEffect(() => {
    // If resource is set in url parameters, select the relevant filters.
    if (urlResource && urlResource !== []) {
      // Set location filter.
      if (hasOwnProperty(urlResource, "location")) {
        setLocationFilter([
          {
            value: urlResource.location,
            label: urlResource.locationDisplayName ?? urlResource.location,
          },
        ]);
      }

      // Set resource filter.
      if (hasOwnProperty(urlResource, "resourceMail") && hasOwnProperty(urlResource, "resourceName")) {
        setResourceFilter([
          {
            value: urlResource.resourceMail,
            label: urlResource.resourceDisplayName ?? urlResource.resourceName,
          },
        ]);
      }
    }

    // Set filter params to trigger filtering of resources
    if (urlResource && urlResource.location && urlResource.resourceMail) {
      setFilterParams({
        "location[]": urlResource.location,
        "resourceMail[]": urlResource.resourceMail,
      });
    }

    // Use data from url parameters.
    if (validUrlParams && Object.values(calendarSelection).length === 0 && urlResource) {
      setDate(new Date(validUrlParams.from));

      setCalendarSelection({
        start: new Date(validUrlParams.from),
        end: new Date(validUrlParams.to),
        allDay: false,
        resource: urlResource,
      });
    }
  }, [urlResource]);

  // Find resources that match filterParams.
  useEffect(() => {
    setLoadingFiltering(true);

    const newFilteredResources = filterAllResources(allResources, filterParams);

    setFilteredResources(newFilteredResources);

    // Limit the number of results to resourceLimit
    const limitedResources = [...newFilteredResources];

    limitedResources.length = Math.min(limitedResources.length, resourceLimit);

    setResources(limitedResources);

    if (Object.values(filterParams).length > 0) {
      setUserHasInteracted(true);
    }
  }, [filterParams]);

  // Set selection as json.
  useEffect(() => {
    if (config?.output_field_id) {
      document.getElementById(config.output_field_id).value = JSON.stringify({
        start: calendarSelection?.start,
        end: calendarSelection?.end,
        resourceId: calendarSelection?.resourceId ?? calendarSelection?.resource?.resourceMail,
        ...authorFields,
      });
    }
  }, [calendarSelection, authorFields]);

  const displayInfoBox = config?.info_box_color && config?.info_box_header && config?.info_box_content;

  const onTabChange = (tab) => {
    setActiveTab(tab);

    setLoadingFiltering(true);
  };

  return (
    <>
      {config && !showSystemFailure && (
        <div className="App">
          {(loadingResources || loadingUserInformation) && (
            <div className="container-fluid">
              <div className="app-wrapper" style={{ minHeight: "100px" }}>
                <LoadingSpinner />
              </div>
            </div>
          )}
          {!loadingResources && !loadingUserInformation && (
            <div className="container-fluid">
              <MainNavigation config={config} />
              <div className="app-wrapper">
                {displayState === "maximized" && (
                  <div className="app-content">
                    <CreateBookingFilters
                      filterParams={filterParams}
                      setFilterParams={setFilterParams}
                      allResources={allResources}
                      disabled={(showResourceDetails !== null || validUrlParams !== null) ?? false}
                      userType={userInformation?.userType}
                      locationFilter={locationFilter}
                      setLocationFilter={setLocationFilter}
                      resourceFilter={resourceFilter}
                      setResourceFilter={setResourceFilter}
                      resourceCategoryFilter={resourceCategoryFilter}
                      setResourceCategoryFilter={setResourceCategoryFilter}
                      facilityFilter={facilityFilter}
                      setFacilityFilter={setFacilityFilter}
                      setAppParams={setAppParams}
                    />

                    {displayInfoBox && <InfoBox config={config} />}

                    <CreateBookingTabs
                      activeTab={activeTab}
                      onTabChange={onTabChange}
                      disabled={showResourceDetails !== null ?? false}
                    />

                    <div className="row no-gutter main-container">
                      {/* Map view */}
                      {activeTab === "map" && (
                        <div className="map">
                          <MapWrapper
                            resources={resources}
                            allResources={allResources}
                            config={config}
                            setLocationFilter={setLocationFilter}
                            setResourceFilter={setResourceFilter}
                            setBookingView={onTabChange}
                            useLocations={false}
                            setFacilityFilter={setFacilityFilter}
                            filterParams={filterParams}
                          />
                        </div>
                      )}

                      {/* List view */}
                      {activeTab === "list" && (
                        <div className={`list ${showResourceDetails !== null ? "resourceview-visible" : ""}`}>
                          <ListContainer
                            resources={resources}
                            setShowResourceDetails={setShowResourceDetails}
                            userHasInteracted={userHasInteracted}
                            isLoading={loadingFiltering}
                            setIsLoading={setLoadingFiltering}
                          />

                          {filteredResources?.length > resourceLimit && (
                            <div style={{ textAlign: "right", padding: "1em" }}>
                              Viser {resources.length} ud af {filteredResources.length} resultater. Filtrér yderligere
                              for at begrænse resultater.
                            </div>
                          )}
                        </div>
                      )}

                      {/* Calendar view */}
                      {activeTab === "calendar" && (
                        // {/* Display calendar for selections */}
                        <div className={`calendar ${showResourceDetails !== null ? "resourceview-visible" : ""}`}>
                          <Calendar
                            resources={resources}
                            date={date}
                            setDate={setDate}
                            calendarSelection={calendarSelection}
                            setCalendarSelection={setCalendarSelection}
                            config={config}
                            setShowResourceDetails={setShowResourceDetails}
                            urlResource={urlResource}
                            setDisplayState={setDisplayState}
                            showResourceDetails={showResourceDetails}
                            userHasInteracted={userHasInteracted}
                            isLoading={loadingFiltering}
                            setIsLoading={setLoadingFiltering}
                          />

                          {filteredResources?.length > resourceLimit && (
                            <div style={{ textAlign: "right", padding: "1em" }}>
                              Viser {resources.length} ud af {filteredResources.length} resultater. Filtrér yderligere
                              for at begrænse resultater.
                            </div>
                          )}
                        </div>
                      )}
                      {showResourceDetails && (
                        <ResourceDetails
                          showResourceDetails={showResourceDetails}
                          setShowResourceDetails={setShowResourceDetails}
                          resource={
                            urlResource ?? allResources.filter((el) => el.resourceMail === showResourceDetails)[0]
                          }
                        />
                      )}
                    </div>
                  </div>
                )}

                {allResources && displayState === "minimized" && calendarSelection !== {} && (
                  <div className="row">
                    <MinimizedDisplay
                      setDisplayState={setDisplayState}
                      resource={
                        urlResource ?? allResources.filter((el) => el.resourceMail === calendarSelection.resourceId)[0]
                      }
                      calendarSelection={calendarSelection}
                    />
                  </div>
                )}

                {/* Display author fields if user is logged in */}
                {!config?.step_one && (
                  <div className="row no-gutter">
                    {authorFields && <AuthorFields authorFields={authorFields} setAuthorFields={setAuthorFields} />}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      )}
      {showSystemFailure && <SystemFailureScreen />}
    </>
  );
}

CreateBooking.propTypes = {
  config: PropTypes.shape({
    api_endpoint: PropTypes.string.isRequired,
    license_key: PropTypes.string,
    output_field_id: PropTypes.string,
    info_box_color: PropTypes.string,
    info_box_header: PropTypes.string,
    info_box_content: PropTypes.string,
    step_one: PropTypes.bool,
  }).isRequired,
};

export default CreateBooking;
