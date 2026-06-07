import React, { useEffect, useState } from 'react';
import Button from 'react-bootstrap/Button';
import Modal from 'react-bootstrap/Modal';
import { useTranslation } from 'react-i18next';


function ErrorRegistration({ show, handleClose, errorMessages, successMessage }) {

  const { t, i18n } = useTranslation();
  const [countdown, setCountdown] = useState(5);

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const langFromUrl = urlParams.get("lang");
    if (langFromUrl && ["en", "fi", "sv"].includes(langFromUrl)) {
      i18n.changeLanguage(langFromUrl);
    }
  }, [i18n, urlParams]);

  // Reset and start countdown when success modal opens
  useEffect(() => {
    if (show && successMessage) {
      setCountdown(5);
      const interval = setInterval(() => {
        setCountdown((prev) => {
          if (prev <= 1) {
            clearInterval(interval);
            setTimeout(() => {
              handleClose();
              window.location.href = '/login';
            }, 200);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
      return () => clearInterval(interval);
    }
  }, [show, successMessage, handleClose]);

  const localizedErrorMessages = errorMessages.map((message) => {
    switch (message) {
      case 'The email has already been taken.':
        return t('email_error');
      case 'The email format is invalid':
        return t('email_error_valid');
      case 'The domain has already been taken.':
        return t('domain_error');
      case 'The domain format is invalid.':
        return t('domain_error_valid');
      default:
        return message;
    }
  });

  return (
    <>
      <Modal show={show} onHide={handleClose} animation={false} centered>
        <Modal.Header closeButton>
          <Modal.Title>{successMessage ? t('success_registration') : t('error')}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {successMessage ? (
            <>
              <p>{successMessage}</p>
              <div style={{
                marginTop: '16px',
                padding: '12px 16px',
                background: 'linear-gradient(135deg, #e8f5e9, #f1f8e9)',
                borderRadius: '8px',
                border: '1px solid #c8e6c9',
                display: 'flex',
                alignItems: 'center',
                gap: '12px'
              }}>
                {/* Circular countdown timer */}
                <div style={{ position: 'relative', width: '52px', height: '52px', flexShrink: 0 }}>
                  <svg width="52" height="52" viewBox="0 0 52 52" style={{ transform: 'rotate(-90deg)' }}>
                    <circle
                      cx="26" cy="26" r="22"
                      fill="none"
                      stroke="#e0e0e0"
                      strokeWidth="4"
                    />
                    <circle
                      cx="26" cy="26" r="22"
                      fill="none"
                      stroke="#4caf50"
                      strokeWidth="4"
                      strokeDasharray={`${2 * Math.PI * 22}`}
                      strokeDashoffset={`${2 * Math.PI * 22 * (1 - countdown / 5)}`}
                      strokeLinecap="round"
                      style={{ transition: 'stroke-dashoffset 0.9s linear' }}
                    />
                  </svg>
                  <div style={{
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    fontSize: '16px',
                    fontWeight: 'bold',
                    color: '#2e7d32'
                  }}>
                    {countdown}
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: '14px', fontWeight: '600', color: '#1b5e20' }}>
                    {t('redirecting_to_login') || 'Redirecting to login...'}
                  </div>
                  <div style={{ fontSize: '12px', color: '#388e3c', marginTop: '2px' }}>
                    {countdown > 0
                      ? (t('seconds_remaining') || `${countdown} seconds remaining`).replace('{count}', countdown)
                      : (t('redirecting_now') || 'Redirecting now...')}
                  </div>
                </div>
              </div>
              <div style={{ marginTop: '12px', fontSize: '14px', color: '#1b5e20' }}>
                {countdown > 0
                  ? `${countdown} ${t('seconds_remaining') || 'seconds remaining until redirect.'}`
                  : (t('redirecting_now') || 'Redirecting now...')}
              </div>
            </>
          ) : (
            <>
              <p>{t('error_messages')}</p>
              <ul>
                {localizedErrorMessages.map((message, index) => (
                  <li key={index}>{message}</li>
                ))}
              </ul>
              <p>{t('end_message')}</p>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={handleClose}>
            {t('close')}
          </Button>
        </Modal.Footer>
      </Modal>
    </>
  );
}

export default ErrorRegistration;