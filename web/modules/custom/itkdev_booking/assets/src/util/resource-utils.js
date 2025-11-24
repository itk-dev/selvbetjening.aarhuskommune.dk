import React from "react";
import { ReactComponent as IconProjector } from "../assets/projector.svg";
import { ReactComponent as IconWheelchair } from "../assets/wheelchair.svg";
import { ReactComponent as IconVideoCamera } from "../assets/videocamera.svg";
import { ReactComponent as IconFood } from "../assets/food.svg";
import { ReactComponent as IconCandles } from "../assets/candles.svg";
import { ReactComponent as IconTelecoil } from "../assets/telecoil.svg";

/**
 * Get facilities for a resource.
 *
 * @param {object} resource The resource object to get facilities from.
 * @returns {object} Object of facility objects.
 */
export default function getResourceFacilities(resource) {
  let resourceObj = resource;

  if (resource.extendedProps) {
    resourceObj = resource.extendedProps;
  }

  return {
    ...(resourceObj.monitorEquipment && {
      monitorequipment: {
        title: "Projektor / Skærm",
        icon: <IconProjector />,
      },
    }),
    ...(resourceObj.wheelchairAccessible && {
      wheelchairaccessible: {
        title: "Handicapvenligt",
        icon: <IconWheelchair />,
      },
    }),
    ...(resourceObj.videoConferenceEquipment && {
      videoconferenceequipment: {
        title: "Videoconference",
        icon: <IconVideoCamera />,
      },
    }),
    ...(resourceObj.catering && {
      catering: {
        title: "Forplejning",
        icon: <IconFood />,
      },
    }),
    ...(resourceObj.telecoil && {
      telecoil: {
        title: "Teleslynge",
        icon: <IconTelecoil />,
      },
    }),
    ...(resourceObj.holidayOpeningHours && {
      holidayOpeningHours: {
        title: "Tilgængelig på helligdag",
        icon: <IconCandles />,
      },
    }),
  };
}
