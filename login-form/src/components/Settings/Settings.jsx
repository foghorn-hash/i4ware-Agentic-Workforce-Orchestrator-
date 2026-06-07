import React, {useEffect, useState} from "react";
import {withRouter} from "react-router-dom";
import {ACCESS_TOKEN_NAME, API_BASE_URL, API_DEFAULT_LANGUAGE, APP_DOMAIN_ADMIN} from "../../constants/apiConstants";
import axios from "axios";
import "./Settings.css";
import './BankAccountsForm.css';
import request from "../../utils/Request";
import { getPaymentTermLabel } from "../../utils/paymentTerms";
import {AuthContext} from "../../contexts/auth.contexts";
import PermissionGate from "../../contexts/PermissionGate";
import Spinner from "react-bootstrap/Spinner";
import Alert from "react-bootstrap/Alert";
import { useTranslation } from "react-i18next";

const ibanRegex = /^[A-Z]{2}[0-9A-Z]{13,32}$/i; // yksinkertainen IBAN-muistutus: maatunnus + 13-32 merkkiä
const bicRegex = /^[A-Za-z]{4}[A-Za-z]{2}[A-Za-z0-9]{2}([A-Za-z0-9]{3})?$/; // 8 tai 11 merkkiä

function normalizeIban(value) {
  return value.replace(/\s+/g, '').toUpperCase();
}

function Settings() {
  const { t, i18n } = useTranslation();
  const {authState, authActions} = React.useContext(AuthContext);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = React.useState(null);
  const [setting, setSetting] = React.useState({
    show_captcha: false,
    disable_registeration_from_others: false,
    saas_price_per_month_per_user: null,
  });
  const [form, setForm] = useState({
      iban1: '', bic1: '',
      iban2: '', bic2: '',
      iban3: '', bic3: ''
  });
  const [paymentTerms, setPaymentTerms] = useState([]);
  const [isLoadingPaymentTerms, setIsLoadingPaymentTerms] = useState(false);
  const [newPaymentTerm, setNewPaymentTerm] = useState({ name: '', days: '' });
  const [editingTermId, setEditingTermId] = useState(null);
  const [editingTerm, setEditingTerm] = useState({ name: '', days: '' });
  const [file, setFile] = React.useState(null);
  const [templateExists, setTemplateExists] = React.useState(false);
  const handleFileChange = e => setFile(e.target.files[0]);
  const currentDomain = authState.user?.domain || "";
  const isAdminDomain = currentDomain === APP_DOMAIN_ADMIN;
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [invoiceStartNumber, setInvoiceStartNumber] = useState("");
  const lang = i18n.language || API_DEFAULT_LANGUAGE;

  // Sync language from URL
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const langFromUrl = urlParams.get("lang");
    if (langFromUrl && ["en", "fi", "sv"].includes(langFromUrl)) {
      i18n.changeLanguage(langFromUrl);
    }
  }, [i18n]);

  useEffect(() => {
    const fetchInvoiceStartNumber = async () => {
      try {
        const res = await request().get("/api/manage/get-invoice-start-number");
        setInvoiceStartNumber(res.data.invoice_start_number || "");
      } catch (err) {
        console.error("Could not load invoice start number:", err);
      }
    };
    fetchInvoiceStartNumber();
  }, []);


  const updateInvoiceStartNumber = async (value) => {
      try {
      const res = await axios.post(
        API_BASE_URL + '/api/manage/update-invoice-start-number', { invoice_start_number: value },
        {
          headers: {
            'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
          }
        }
      );
      setMessage(t('settingUpdated'));
      setTimeout(() => setMessage(null), 2500);
    } catch (err) {
      setMessage(err.response?.data?.message || t('error_updating_invoice_start_number'));
    }
  };

  const uploadInvoiceTemplate = async (file) => {
    const formData = new FormData();
    formData.append('invoice_template_path', file);

    try {
      const res = await axios.post(
        API_BASE_URL + '/api/manage/upload-invoice-template',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
          }
        }
      );
      setMessage(t('uploadedSuccessfully') || "Uploaded successfully.");
      setTemplateExists(true);
      setTimeout(() => {
          setMessage(null);
        }, 2500);
    } catch (err) {
      setMessage(t('uploadFailed') || "Upload failed. Please try again.");
      setTimeout(() => {
          setMessage(null);
        }, 2500);
    }
  };

  useEffect(() => {
    const checkTemplateExists = async () => {
      try {
        const res = await axios.get(
          API_BASE_URL + '/api/manage/template-exists',
          {
            headers: {
              'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
            }
          }
        );
        setTemplateExists(res.data.exists);
      } catch (err) {
        console.error("Could not check template:", err);
      }
    };
    checkTemplateExists();
  }, []);

  useEffect(() => {
    const fetchSettings = async () => {
      setIsLoading(true);

      try {
        const res = await axios.get(
          API_BASE_URL + '/api/manage/settings',
          {
            headers: {
              'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
            }
          }
        );
        if (res.status === 200) {
          const obj = {};
          res.data.data.forEach(element => {
            if (element.setting_value === "1") obj[element.setting_key] = true;
            else if (element.setting_value === "0") obj[element.setting_key] = false;
            else obj[element.setting_key] = element.setting_value;
          });
          setSetting(obj);
        }
        setIsLoading(false);
      } catch (err) {
        console.error("Could not fetch settings:", err);
        setError(t('error_fetching_settings') || "Error fetching settings");
        setIsLoading(false);
      }
    };
    fetchSettings();
  }, []);

  const settingUpdate = data => {
    request()
      .post("/api/manage/updateSettings", data)
      .then(res => {
        setMessage(t('settingUpdated'));
        
        setTimeout(() => {
          setMessage(null);
        }, 2500);
      })
  };

  useEffect(() => {

    const fetchBankAccounts = async () => {
      try {
        const res = await axios.get(
          API_BASE_URL + '/api/manage/bank-accounts',
          {
            headers: {
              'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
            }
          }
        );
        setForm({
            iban1: res.data.iban1 || '',
            bic1: res.data.bic1 || '',
            iban2: res.data.iban2 || '',
            bic2: res.data.bic2 || '',
            iban3: res.data.iban3 || '',
            bic3: res.data.bic3 || '',
          });
      } catch (err) {
          console.error("Could not fetch bank accounts:", err);
          setError(t('error_fetching_bank_accounts') || "Error fetching bank accounts");
      }
    };

    const fetchPaymentTerms = async () => {
      setIsLoadingPaymentTerms(true);
      try {
        const currentLocale = lang || API_DEFAULT_LANGUAGE;
        const res = await axios.get(
          API_BASE_URL + '/api/manage/i18n/payment-terms',
          {
            params: {
              i18n: currentLocale.toUpperCase()
            },
            headers: {
              'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
            }
          }
        );
        setPaymentTerms(res.data.data || res.data || []);
      } catch (err) {
        console.error("Could not fetch payment terms:", err);
      } finally {
        setIsLoadingPaymentTerms(false);
      }
    };

    fetchBankAccounts();
    fetchPaymentTerms();

  }, []);

  const handleBankAccountsSubmit = async(e) => {
    e.preventDefault();
    
    const validationErrors = validateAll();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      setMessage({ type: 'error', text: t('bankaccountsUpdateFailed') || "Please fix the errors in the form." });
      return;
    }

    setTouched({
      iban1:true,bic1:true,iban2:true,bic2:true,iban3:true,bic3:true
    });

    setSubmitting(true);
    setMessage(null);

      try {
        const res = await axios.post(
          API_BASE_URL + '/api/manage/bank-accounts', form,
          {
            headers: {
              'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
            }
          }
        );
        setMessage({ type: 'success', text: t('bankaccountsUpdated') || "Bank accounts updated successfully." });
        setTimeout(() => setMessage(null), 2500);
        setSubmitting(false);
      } catch (err) {
        console.error(err);
        setMessage({ type: 'error', text: t('bankaccountsUpdateFailed') || "Failed to update bank accounts."  });
        setTimeout(() => setMessage(null), 2500);
        setSubmitting(false);
      }
  };

  const validateField = (name, value) => {
    if (name.startsWith('iban')) {
      const v = normalizeIban(value);
      if (!v) return null; // tyhjä sallittu
      if (v.length < 15 || v.length > 34) return t('iban_length_invalid');
      if (!ibanRegex.test(v)) return t('iban_format_invalid');
      return null;
    }

    if (name.startsWith('bic')) {
      const v = value.trim().toUpperCase();
      if (!v) return null;
      if (!bicRegex.test(v)) return t('bic_format_invalid');
      return null;
    }

    return null;
  }

  const validateAll = () => {
    const nextErrors = {};
    Object.entries(form).forEach(([k, v]) => {
      const e = validateField(k, v);
      if (e) nextErrors[k] = e;
    });
    return nextErrors;
  }

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
    if (touched[name]) {
      setErrors(prev => ({ ...prev, [name]: validateField(name, value) }));
    }
  }

  const handleBlur = (e) => {
    const { name, value } = e.target;
    setTouched(prev => ({ ...prev, [name]: true }));
    setErrors(prev => ({ ...prev, [name]: validateField(name, value) }));
  }

  const Field = ({ label, name, placeholder }) => (
    <div className="mb-4">
      <label htmlFor={name} className="bank-accounts-label">{label}</label>
      <input
        id={name}
        name={name}
        value={form[name]}
        onChange={handleChange}
        onBlur={handleBlur}
        placeholder={placeholder}
        className={`field ${errors[name] ? 'bank-accounts-label-input-red' : 'bank-accounts-label-input-gray'}`}
        aria-invalid={errors[name] ? 'true' : 'false'}
        aria-describedby={errors[name] ? `${name}-error` : undefined}
      />
      {errors[name] && (
        <p id={`${name}-error`} className="mt-1 text-sm text-red-600">{errors[name]}</p>
      )}
    </div>
  );

  const handleAddPaymentTerm = async () => {
    if (!newPaymentTerm.name || !newPaymentTerm.days) {
      return;
    }

    try {
      const currentLocale = (lang || API_DEFAULT_LANGUAGE || 'en').toLowerCase();
      const res = await axios.post(
        API_BASE_URL + '/api/manage/i18n/payment-terms',
        {
          days: newPaymentTerm.days,
          i18n_translations: {
            [currentLocale]: newPaymentTerm.name
          }
        },
        {
          headers: {
            'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
          }
        }
      );
      
      // Lisää uusi termi listaan
      const newTerm = res.data.data;
      if (newTerm) {
        setPaymentTerms([...paymentTerms, newTerm]);
      }
      
      setNewPaymentTerm({ name: '', days: '' });
      setMessage({ type: 'success', text: t('payment_terms_updated') || "Payment terms updated successfully." });
      setTimeout(() => setMessage(null), 2500);
    } catch (err) {
      console.error(err);
      setMessage({ type: 'error', text: t('payment_terms_update_failed') || "Failed to update payment terms." });
      setTimeout(() => setMessage(null), 2500);
    }
  };

  const handleDeletePaymentTerm = async (id) => {
    if (!window.confirm(t('confirm_delete') || "Are you sure you want to delete this payment term?")) {
      return;
    }

    try {
      await axios.delete(
        API_BASE_URL + `/api/manage/i18n/payment-terms`,
        {
          data: { id: id },
          headers: {
            'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
          }
        }
      );
      setPaymentTerms(paymentTerms.filter(term => term.id !== id));
      setMessage({ type: 'success', text: t('payment_terms_updated') || "Payment terms updated successfully." });
      setTimeout(() => setMessage(null), 2500);
    } catch (err) {
      console.error(err);
      setMessage({ type: 'error', text: t('payment_terms_update_failed') || "Failed to update payment terms." });
      setTimeout(() => setMessage(null), 2500);
    }
  };

  const handleEditPaymentTerm = (term) => {
    setEditingTermId(term.id);
    setEditingTerm({ name: getPaymentTermLabel(term), days: term.days_to_pay });
  };

  const handleCancelEdit = () => {
    setEditingTermId(null);
    setEditingTerm({ name: '', days: '' });
  };

  const handleSavePaymentTerm = async (id) => {
    if (!editingTerm.name || !editingTerm.days) {
      return;
    }

    try {
      const currentLocale = lang || API_DEFAULT_LANGUAGE;
      const res = await axios.put(
        API_BASE_URL + `/api/manage/i18n/payment-terms` + `/${id}`,
        {
          days: editingTerm.days,
          i18n_translations: {
            [currentLocale.toLowerCase()]: editingTerm.name
          }
        },
        {
          params: {
            i18n: currentLocale.toUpperCase()
          },
          headers: {
            'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME)
          }
        }
      );
      
      // Update the term in the list
      const updatedTerm = res.data.data;
      if (updatedTerm) {
        setPaymentTerms(paymentTerms.map(term => 
          term.id === id ? updatedTerm : term
        ));
      }
      
      setEditingTermId(null);
      setEditingTerm({ name: '', days: '' });
      setMessage({ type: 'success', text: t('payment_terms_updated') || "Payment terms updated successfully." });
      setTimeout(() => setMessage(null), 2500);
    } catch (err) {
      console.error(err);
      setMessage({ type: 'error', text: t('payment_terms_update_failed') || "Failed to update payment terms." });
      setTimeout(() => setMessage(null), 2500);
    }
  };

  if (setting.length === 0 && isLoading) {
    return (
      <div className="text-center mt-4">
        <Spinner animation="border" />
      </div>
    );
  }

  if (error) {
    return <Alert variant="danger">{error}</Alert>;
  }

  return (
    <div className="mt-2">
	{<PermissionGate permission={"settings.manage"} >
      <div className="mt-5">
        {message && (
          <div className={`alert ${typeof message === 'object' ? (message.type === 'error' ? 'alert-danger' : 'alert-success') : 'alert-success'}`}>
            {typeof message === 'object' ? message.text : message}
          </div>
        )}
        {isAdminDomain && (
        <>
        <div className="form-check">
          <input
            className="form-check-input"
            type="checkbox"
            value=""
            id="defaultCheck1"
            onChange={e => {
              settingUpdate({
                setting_key: "show_captcha",
                setting_value: e.target.checked,
              });

              setSetting({
                ...setting,
                show_captcha: e.target.checked
              })
            }}
            checked={setting.show_captcha}
          />
          <label className="form-check-label" htmlFor="defaultCheck1">
            {t('showCaptcha') || "Show captcha on login page"}
          </label>
          <br />
          <input
            className="form-check-input"
            type="checkbox"
            value=""
            id="defaultCheck2"
            onChange={e => {
              settingUpdate({
                setting_key: "disable_registeration_from_others",
                setting_value: e.target.checked,
              });

              setSetting({
                ...setting,
                disable_registeration_from_others: e.target.checked
              })
            }}
            checked={setting.disable_registeration_from_others}
          />
          <label className="form-check-label" htmlFor="defaultCheck2">
            {t('disableRegistration') || "Disable registration for others"}
          </label>
        </div>
        <div className="mb-3">
            <label htmlFor="saas_price_per_month_per_user" className="form-label-price">{t('saas_price_per_month_per_user') || "SaaS price per month per user (in EUR)"}</label>
            <input
              type="number"
              className="form-control-price"
              id="saas_price_per_month_per_user"
              value={setting.saas_price_per_month_per_user || ""}
              onChange={e => {
                const value = parseInt(e.target.value) || 0;
                setSetting(prev => ({ ...prev, saas_price_per_month_per_user: value }));
                settingUpdate({ setting_key: "saas_price_per_month_per_user", setting_value: value });
              }}
            />
          </div>
        </>
        )}
            <hr />

        {/* Excel Template Upload */}
        <PermissionGate permission={"invoice.uploadTemplates"}>
          <div className="mt-3 invoice-upload">
            <label htmlFor="invoiceTemplate" className="form-label">{t('upload_template') || "Upload Invoice Template (MS Excel - .xlsx)"}</label>
            <input
              type="file"
              className="file-input"
              id="invoiceTemplate"
              accept=".xlsx"
              onChange={handleFileChange}
              style={{ display: 'none' }}
            />
            <button
              className="browse-btn btn btn-outline-secondary"
              onClick={() => document.getElementById("invoiceTemplate").click()}
            >
              {t('browse_file') || "Browse File"}
            </button>

            {/* Näytetään valittu tiedosto */}
            <span className={`file-name ${file ? '' : 'empty'}`}>
              {file ? file.name : t('no_file_selected') || "No file selected"}
            </span>
            <button
              className="upload-btn btn btn-primary"
              onClick={() => uploadInvoiceTemplate(file) }
            >
              {t('upload') || "Upload"}
            </button>
            <div className="mt-2">
              {templateExists ? (
                <div className="alert alert-info">
                  {t('template_already_uploaded') || "Template is already uploaded."}
                </div>
              ) : (
                <div className="alert alert-warning">
                  {t('template_not_uploaded') || "No template uploaded yet."}
                </div>
              )}
            </div>
          </div>
        </PermissionGate>
        {/* Invoice Start Number */}
        <div className="mt-3">
          <label htmlFor="invoiceStartNumber" className="form-label">{t('invoice_start_number') || "Invoice Start Number"}</label>
          <input
            type="number"
            className="form-control"
            id="invoiceStartNumber"
            value={invoiceStartNumber}
            onChange={e => {
              const value = parseInt(e.target.value) || 0;
              setInvoiceStartNumber(value);
              updateInvoiceStartNumber(value);
            }}
          />
        </div>
        <form onSubmit={handleBankAccountsSubmit} className="bank-accounts-form">
          <h2>{t('bank_accounts_title') || "Bank Accounts"}</h2>

          <div className="bank-accounts-fields">
            <div>
              <Field label={`${t('iban_label')} 1`} name="iban1" placeholder="FI2112345600000000" />
            </div>
            <div>
              <Field label={`${t('bic_label')} 1`} name="bic1" placeholder="NDEAFIHH" />
            </div>

            <div>
              <Field label={`${t('iban_label')} 2`} name="iban2" placeholder={t('optional_placeholder') || "Optional"} />
            </div>
            <div>
              <Field label={`${t('bic_label')} 2`} name="bic2" placeholder={t('optional_placeholder') || "Optional"} />
            </div>

            <div>
              <Field label={`${t('iban_label')} 3`} name="iban3" placeholder={t('optional_placeholder') || "Optional"} />
            </div>
            <div>
              <Field label={`${t('bic_label')} 3`} name="bic3" placeholder={t('optional_placeholder') || "Optional"} />
            </div>
          </div>

          <div className="bank-accounts-buttons">
            <button
              type="submit"
              disabled={submitting}
              className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 disabled:opacity-60"
            >
              {submitting ? t('saving_button') : t('save_button') || (submitting ? "Saving..." : "Save")}
            </button>

            <button
              type="button"
              onClick={() => { setForm({ iban1:'',bic1:'',iban2:'',bic2:'',iban3:'',bic3:'' }); setErrors({}); setTouched({}); setMessage(null); }}
              className="px-3 py-2 border rounded"
            >
              {t('clear_button') || "Clear"}
              </button>

          </div>
          {message && (
              <>
              <div className="settings-clear"></div>
                <div className={`alert ${typeof message === 'object' ? (message.type === 'error' ? 'alert-danger' : 'alert-success') : 'alert-success'}`}>
                  {typeof message === 'object' ? message.text : message}
                </div>
              </>
            )}
          <p className="mt-3 text-xs text-gray-500">{t('bank_accounts_info') || "Bank Accounts Information"}</p>
        </form>

        {/* Payment Terms Section */}
        <div className="mt-5">
          <h3>{t('payment_terms_title') || "Payment Terms"}</h3>
          
          <div className="mb-3">
            <div className="row">
              <div className="col-md-5">
                <input
                  type="text"
                  className="form-control"
                  placeholder={t('term_name') || "Term Name"}
                  value={newPaymentTerm.name}
                  onChange={(e) => setNewPaymentTerm({...newPaymentTerm, name: e.target.value})}
                />
              </div>
              <div className="col-md-3">
                <input
                  type="number"
                  className="form-control"
                  placeholder={t('days') || "Days"}
                  value={newPaymentTerm.days}
                  onChange={(e) => setNewPaymentTerm({...newPaymentTerm, days: e.target.value})}
                />
              </div>
              <div className="col-md-4">
                <button 
                  type="button" 
                  className="btn btn-primary"
                  onClick={handleAddPaymentTerm}
                >
                  {t('add_payment_term') || "Add Payment Term"}
                </button>
              </div>
            </div>
          </div>

          <table className="table table-striped terms-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>{t('term_name') || "Term Name"}</th>
                <th>{t('days') || "Days"}</th>
                <th>{t('actions') || "Actions"}</th>
              </tr>
            </thead>
            <tbody>
              {isLoadingPaymentTerms ? (
                <tr>
                  <td colSpan="4" className="text-center">
                    <Spinner animation="border" />
                  </td>
                </tr>
              ) : paymentTerms.length > 0 ? (
                paymentTerms.map((term) => (
                  <tr key={term.id}>
                    {editingTermId === term.id ? (
                      <>
                        <td>{term.id}</td>
                        <td>
                          <input
                            type="text"
                            className="form-control form-control-sm"
                            value={editingTerm.name}
                            onChange={(e) => setEditingTerm({...editingTerm, name: e.target.value})}
                          />
                        </td>
                        <td>
                          <input
                            type="number"
                            className="form-control form-control-sm"
                            value={editingTerm.days}
                            onChange={(e) => setEditingTerm({...editingTerm, days: e.target.value})}
                          />
                        </td>
                        <td>
                          <button
                            className="btn btn-sm btn-success me-1"
                            onClick={() => handleSavePaymentTerm(term.id)}
                          >
                            {t('save') || "Save"}
                          </button>
                          <button
                            className="btn btn-sm btn-secondary"
                            onClick={handleCancelEdit}
                          >
                            {t('cancel') || "Cancel"}
                          </button>
                        </td>
                      </>
                    ) : (
                      <>
                        <td>{term.id}</td>
                        <td>{getPaymentTermLabel(term)}</td>
                        <td>{term.days_to_pay}</td>
                        <td>
                          <button
                            className="btn btn-sm btn-primary me-1"
                            onClick={() => handleEditPaymentTerm(term)}
                          >
                            {t('edit') || "Edit"}
                          </button>
                          <button
                            className="btn btn-sm btn-danger"
                            onClick={() => handleDeletePaymentTerm(term.id)}
                          >
                            {t('delete') || "Delete"}
                          </button>
                        </td>
                      </>
                    )}
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="4" className="text-center text-muted">
                    {t('no_terms_selected') || "No payment terms selected"}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

      </div>
      </PermissionGate>
	  }
    </div>
  );
}

export default withRouter(Settings);
