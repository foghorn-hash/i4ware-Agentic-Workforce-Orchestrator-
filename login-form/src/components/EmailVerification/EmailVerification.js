import React, { useEffect, useState } from 'react';
import axios from 'axios';
import './EmailVerification.css';
import { API_BASE_URL, ACCESS_TOKEN_NAME } from '../../constants/apiConstants';
import { withRouter } from "react-router-dom";
import VerificationComponent from '../../components/VerificationComponent/VerificationComponent';
import { useTranslation } from 'react-i18next';

function EmailVerification(props) {
  const { t, i18n } = useTranslation();
  const [state, setState] = useState({
    successMessage: null,
    success: false,
    countdown: 0
  });
  const handleChange = (e) => {
    const { id, value } = e.target
    setState(prevState => ({
      ...prevState,
      [id]: value
    }))
  };

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const langFromUrl = urlParams.get("lang");
    if (langFromUrl && ["en", "fi", "sv"].includes(langFromUrl)) {
      i18n.changeLanguage(langFromUrl);
    }
  }, [i18n, urlParams]);

  // When a verification succeeds, start a countdown and redirect when it reaches 0
  useEffect(() => {
    if (!state.success) return;

    let timerId = null;

    if (state.countdown > 0) {
      timerId = setInterval(() => {
        setState(prev => {
          if (prev.countdown <= 1) {
            // final tick: clear interval and redirect
            clearInterval(timerId);
            redirectToLogin();
            return { ...prev, countdown: 0 };
          }
          return { ...prev, countdown: prev.countdown - 1 };
        });
      }, 1000);
    }

    return () => {
      if (timerId) clearInterval(timerId);
    };
  }, [state.success, state.countdown]);



  const redirectToLogin = () => {
    window.location.hash = '#/login';
  }

  return (
    <div className="d-flex justify-content-center">
      <div className="card col-12 col-lg-4 verification-card mt-2 hv-center">
        <VerificationComponent onSuccess={(msg) => setState(prev => ({
          ...prev,
          success: true,
          successMessage: msg || t('verificationSuccess'),
          countdown: 5
        }))} />
        {state.successMessage && (
          <div className="alert alert-success mt-2" role="alert">
            {state.successMessage}
            {state.countdown > 0 && (
              <span className="ml-2">{t('redirectingIn', { seconds: state.countdown }) || ` Redirecting in ${state.countdown}...`}</span>
            )}
          </div>
        )}
        <div className="mt-2">
          <span className="account-question">{t('goToLogin')}</span>
          <span className="verificationText" onClick={() => redirectToLogin()}> {t('loginHere')}</span>
        </div>
      </div>
    </div>
  )
};

export default withRouter(EmailVerification);