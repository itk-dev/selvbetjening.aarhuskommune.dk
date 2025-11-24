import React from "react";
import * as PropTypes from "prop-types";
import "./author-fields.scss";

/**
 * Author fields component.
 *
 * @param {object} props Props.
 * @param {object} props.authorFields Author fields.
 * @param {Function} props.setAuthorFields Set author fields function.
 * @returns {JSX.Element} Author fields component.
 */
function AuthorFields({ authorFields, setAuthorFields }) {
  const onChangeEmail = (event) => {
    setAuthorFields({ ...authorFields, email: event.target.value });
  };

  const onChangeSubject = (event) => {
    setAuthorFields({ ...authorFields, subject: event.target.value });
  };

  return (
    <div className="col-md-12 author-fields-wrapper">
      <h2 className="webform-section-title">Nødvendig information: </h2>
      <div className="webform-section-wrapper">
        <div className="form-item">
          <label htmlFor="subject-input" className="form-item__label">
            <span className="form-item-label">Bookingtitel</span>
            <input
              id="subject-input"
              type="text"
              placeholder="Booking titel"
              required
              value={authorFields.subject}
              onChange={onChangeSubject}
              className="form-element"
            />
            <div class="description form-item__description">
              <div class="webform-element-description">
                <p>
                  <strong>Bemærk</strong>
                  : Den ovenstående titel kan blive vist på en infoskærm. Angiv venligst ikke følsomme oplysninger i titlen på din booking.
                </p>
              </div>
            </div>
          </label>
        </div>
        <div className="form-item">
          <label htmlFor="email-input" className="form-item__label">
            <span className="form-item-label">E-mail</span>
            <input
              id="email-input"
              type="email"
              autoComplete="email"
              placeholder="E-mail"
              required
              value={authorFields.email}
              onChange={onChangeEmail}
              className="form-element"
            />
          </label>
        </div>
      </div>
    </div>
  );
}

AuthorFields.propTypes = {
  authorFields: PropTypes.shape({
    email: PropTypes.string.isRequired,
    subject: PropTypes.string.isRequired,
  }).isRequired,
  setAuthorFields: PropTypes.func.isRequired,
};

export default AuthorFields;
