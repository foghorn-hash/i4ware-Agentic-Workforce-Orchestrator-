import React, { useEffect, useState } from 'react';
import { Modal, Form, Button, Table, Alert, Spinner } from 'react-bootstrap';
import { useParams, useHistory, withRouter } from "react-router-dom";
import axios from 'axios';
import { API_BASE_URL, ACCESS_TOKEN_NAME, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import { getPaymentTermLabel } from "../../utils/paymentTerms";
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en: {
    editInvoice: "Edit Invoice",
    customer: "Customer",
    invoiceNumber: "Invoice Number",
    reference_code: "Reference Code",
    invoiceDate: "Invoice Date",
    dueDate: "Due Date",
    status: "Status",
    cancel: "Cancel",
    save: "Save",
    draft: "Draft",
    open: "Open",
    paid: "Paid",
    partial: "Partially Paid",
    overdue: "Overdue",
    overpaid: "Overpaid",
    searchCustomer: "Search Customer",
    searchPlaceholder: "Search by business ID or company name",
    selectCustomer: "Select",
    name: "Name",
    businessId: "Business ID",
    email: "Email",
    noCustomersFound: "No customers found",
    success_updating_invoice: "Invoice updated successfully",
    error_updating_invoice: "Error updating invoice",
    loading: "Loading...",
    invoiceLines: "Invoice Lines",
    description: "Description",
    quantity: "Quantity",
    unit: "Unit",
    unitPriceExclVat: "Unit Price (excl. VAT)",
    unitPriceInclVat: "Unit Price (incl. VAT)",
    vatRate: "VAT %",
    totalExclVat: "Total (excl. VAT)",
    totalInclVat: "Total (incl. VAT)",
    actions: "Actions",
    addLine: "Add Line",
    delete: "Delete",
    noLines: "No invoice lines yet",
    sepa_reference: "SEPA Reference",
    sellers_reference: "Seller's Reference",
    buyers_reference: "Buyer's Reference",
    complaints_within: "Complaints Within"
  },
  fi: {
    editInvoice: "Muokkaa laskua",
    customer: "Asiakas",
    invoiceNumber: "Laskunumero",
    reference_code: "Viitenumero",
    invoiceDate: "Laskun päivämäärä",
    dueDate: "Eräpäivä",
    status: "Tila",
    cancel: "Peruuta",
    save: "Tallenna",
    draft: "Luonnos",
    open: "Avoin",
    paid: "Maksettu",
    partial: "Osittain maksettu",
    overdue: "Erääntynyt",
    overpaid: "Ylimaksettu",
    searchCustomer: "Hae asiakas",
    searchPlaceholder: "Hae Y-tunnuksella tai yrityksen nimellä",
    selectCustomer: "Valitse",
    name: "Nimi",
    businessId: "Y-tunnus",
    email: "Sähköposti",
    noCustomersFound: "Asiakkaita ei löytynyt",
    success_updating_invoice: "Lasku päivitetty onnistuneesti",
    error_updating_invoice: "Virhe laskun päivittämisessä",
    loading: "Ladataan...",
    invoiceLines: "Laskurivit",
    description: "Kuvaus",
    quantity: "Määrä",
    unit: "Yksikkö",
    unitPriceExclVat: "Yksikköhinta (alv 0%)",
    unitPriceInclVat: "Yksikköhinta (sis. alv)",
    vatRate: "ALV %",
    totalExclVat: "Yhteensä (alv 0%)",
    totalInclVat: "Yhteensä (sis. alv)",
    actions: "Toiminnot",
    addLine: "Lisää rivi",
    delete: "Poista",
    noLines: "Ei vielä laskurivejä",
    sepa_reference: "SEPA Viite",
    sellers_reference: "Myyjän viite",
    buyers_reference: "Ostajan viite",
    complaints_within: "Huomautusaika"
  },
  sv: {
    editInvoice: "Redigera faktura",
    customer: "Kund",
    invoiceNumber: "Fakturanummer",
    reference_code: "Referenskod",
    invoiceDate: "Fakturadatum",
    dueDate: "Förfallodatum",
    status: "Status",
    cancel: "Avbryt",
    save: "Spara",
    draft: "Utkast",
    open: "Öppen",
    paid: "Betald",
    partial: "Delvis betald",
    overdue: "Förfallen",
    overpaid: "Överbetald",
    searchCustomer: "Sök kund",
    searchPlaceholder: "Sök med organisationsnummer eller företagsnamn",
    selectCustomer: "Välj",
    name: "Namn",
    businessId: "Organisationsnummer",
    email: "E-post",
    noCustomersFound: "Inga kunder hittades",
    success_updating_invoice: "Faktura uppdaterad",
    error_updating_invoice: "Fel vid uppdatering av faktura",
    loading: "Laddar...",
    invoiceLines: "Fakturarader",
    description: "Beskrivning",
    quantity: "Antal",
    unit: "Enhet",
    unitPriceExclVat: "Enhetspris (exkl. moms)",
    unitPriceInclVat: "Enhetspris (inkl. moms)",
    vatRate: "Moms %",
    totalExclVat: "Totalt (exkl. moms)",
    totalInclVat: "Totalt (inkl. moms)",
    actions: "Åtgärder",
    addLine: "Lägg till rad",
    delete: "Ta bort",
    noLines: "Inga fakturarader ännu",
    sepa_reference: "SEPA Referens",
    sellers_reference: "Säljarens referens",
    buyers_reference: "Köparens referens",
    complaints_within: "Reklamation inom"
  }
});

function EditInvoice() {
  const { id } = useParams();
  const history = useHistory();
  const [invoice, setInvoice] = useState(null);
  const [formData, setFormData] = useState({
    customer_id: '',
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    payment_term_id: '',
    sellers_reference: '',
    buyers_reference: '',
    complaints_within: '',
    status: 'draft'
  });
  const [customers, setCustomers] = useState([]);
  const [customerSearch, setCustomerSearch] = useState('');
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [invoiceLines, setInvoiceLines] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [savingLines, setSavingLines] = useState(false);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

  const invoiceTotal = invoiceLines.reduce((sum, line) => {
    return sum + (parseFloat(line.total_including_vat || 0) || 0);
  }, 0);
  const [paymentTerms, setPaymentTerms] = useState([]);
  const [isLoadingPaymentTerms, setIsLoadingPaymentTerms] = useState(false);

  var query = window.location.search.substring(1);
  var urlParams = new URLSearchParams(query);
  var localization = urlParams.get('lang');

  if (localization === null) {
    strings.setLanguage(API_DEFAULT_LANGUAGE);
  } else {
    strings.setLanguage(localization);
  }

  // Hae maksuehto ensin, sitten lasku
  useEffect(() => {
    const fetchPaymentTerms = async () => {
      setIsLoadingPaymentTerms(true);
      try {
        const currentLocale = localization || API_DEFAULT_LANGUAGE;
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
        const terms = res.data.data || res.data || [];
        setPaymentTerms(terms);
      } catch (err) {
        console.error("Could not fetch payment terms:", err);
      } finally {
        setIsLoadingPaymentTerms(false);
      }
    };

    fetchPaymentTerms().then(() => {
      fetchInvoice();
    });
  }, [id]);

  const fetchInvoice = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await axios.get(`${API_BASE_URL}/api/invoices/show/${id}`, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      
      const invoiceData = response.data.data || response.data;
      setInvoice(invoiceData);
      
      setFormData({
        customer_id: invoiceData.customer_id,
        payment_term_id: invoiceData.payment_term_id || '',
        invoice_number: invoiceData.invoice_number,
        reference_code: invoiceData.reference_code,
        sepa_reference: invoiceData.sepa_reference,
        invoice_date: invoiceData.invoice_date,
        due_date: invoiceData.due_date,
        sellers_reference: invoiceData.sellers_reference || '',
        buyers_reference: invoiceData.buyers_reference || '',
        complaints_within: invoiceData.complaints_within || '',
        status: invoiceData.status
      });
      
      // Hae laskurivit erikseen
      try {
        const linesResponse = await axios.get(`${API_BASE_URL}/api/invoices/${id}/lines`, {
          headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
        });
        const lines = linesResponse.data.data || linesResponse.data || [];
        setInvoiceLines(lines);
      } catch (err) {
        console.error('Error fetching invoice lines:', err);
        setInvoiceLines([]);
      }
      
      // Jos asiakas on mukana vastauksessa, aseta se
      if (invoiceData.customer) {
        setSelectedCustomer(invoiceData.customer);
      } else if (invoiceData.customer_id) {
        // Jos asiakasta ei ole mukana, hae se erikseen
        try {
          const customerResponse = await axios.get(`${API_BASE_URL}/api/customers/show/${invoiceData.customer_id}`, {
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
          });
          const customerData = customerResponse.data.data || customerResponse.data;
          setSelectedCustomer(customerData);
        } catch (err) {
          console.error('Error fetching customer:', err);
        }
      }
      
      setLoading(false);
    } catch (err) {
      console.error('Error fetching invoice:', err);
      setError(err.response?.data?.message || 'Error loading invoice');
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    // Jos muutetaan laskupäivämäärää tai maksuehtoa, laske eräpäivä automaattisesti
    if (name === 'invoice_date' || name === 'payment_term_id') {
      const invoiceDate = name === 'invoice_date' ? value : formData.invoice_date;
      
      if (name === 'payment_term_id') {
        // Etsi payment term ID:llä ja hae days_to_pay
        const selectedTerm = paymentTerms.find(term => String(term.id) === String(value));
        const paymentTermDays = selectedTerm ? parseInt(selectedTerm.days_to_pay) : 0;
        
        if (invoiceDate && paymentTermDays && selectedTerm) {
          const dueDate = new Date(invoiceDate);
          dueDate.setDate(dueDate.getDate() + paymentTermDays);
          
          setFormData(prev => ({
            ...prev,
            payment_term_id: value,
            due_date: dueDate.toISOString().split('T')[0]
          }));
          return;
        }
      } else {
        // invoice_date muuttui - etsi days_to_pay formData.payment_term_id ID:llä
        const selectedTerm = paymentTerms.find(term => String(term.id) === String(formData.payment_term_id));
        const paymentTermDays = selectedTerm ? parseInt(selectedTerm.days_to_pay) : 0;
        
        if (invoiceDate && paymentTermDays) {
          const dueDate = new Date(invoiceDate);
          dueDate.setDate(dueDate.getDate() + paymentTermDays);
          
          setFormData(prev => ({
            ...prev,
            [name]: value,
            due_date: dueDate.toISOString().split('T')[0]
          }));
          return;
        }
      }
    }
    
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const searchCustomers = async (searchTerm) => {
    if (searchTerm.length < 2) {
      setCustomers([]);
      return;
    }
    
    try {
      const response = await axios.get(`${API_BASE_URL}/api/customers/search?q=${searchTerm}`, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      setCustomers(response.data.data || response.data);
    } catch (err) {
      console.error('Error searching customers:', err);
      setCustomers([]);
    }
  };

  const handleCustomerSearch = (e) => {
    const value = e.target.value;
    setCustomerSearch(value);
    searchCustomers(value);
  };

  const handleSelectCustomer = (customer) => {
    setSelectedCustomer(customer);
    setFormData(prev => ({
      ...prev,
      customer_id: customer.id
    }));
    setCustomerSearch('');
    setCustomers([]);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    try {
      const response = await axios.put(`${API_BASE_URL}/api/invoices/update/${id}`, formData, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      
      if (response.data.success) {
        setSuccessMessage(strings.success_updating_invoice);
        setTimeout(() => {
          history.goBack();
        }, 1500);
      }
    } catch (err) {
      console.error('Error updating invoice:', err);
      setError(err.response?.data?.message || strings.error_updating_invoice);
    } finally {
      setSaving(false);
    }
  };

  const fetchInvoiceLines = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/invoices/${id}/lines`, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      
      const lines = response.data.data || response.data || [];
      setInvoiceLines(lines);
    } catch (err) {
      console.error('Error fetching invoice lines:', err);
    }
  };

  const handleAddLine = async () => {
    try {
      const response = await axios.post(`${API_BASE_URL}/api/invoices/${id}/lines/add`, {
        description: '',
        quantity: 0,
        unit: 't',
        unit_price_excluding_vat: 0,
        total_excluding_vat: 0,
        total_including_vat: 0,
        vat: 0
      }, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      
      if (response.data.success || response.data.data) {
        // Päivitä laskurivit
        fetchInvoiceLines();
      }
    } catch (err) {
      console.error('Error adding invoice line:', err);
      setError(err.response?.data?.message || 'Error adding line');
    }
  };

  const handleLineChange = (index, field, value) => {
    const updatedLines = [...invoiceLines];
    const line = { ...updatedLines[index] };
    
    line[field] = value;
    
    // Convert to numbers for calculations
    const quantity = parseFloat(line.quantity) || 0;
    const vat = parseFloat(line.vat) || 0;
    const vatMultiplier = 1 + (vat / 100);
    
    // Calculate based on which field changed
    if (field === 'unit_price_excluding_vat') {
      const priceExcl = parseFloat(value) || 0;
      line.unit_price_including_vat = (priceExcl * vatMultiplier).toFixed(2);
      line.total_excluding_vat = (priceExcl * quantity).toFixed(2);
      line.total_including_vat = (priceExcl * quantity * vatMultiplier).toFixed(2);
    } else if (field === 'unit_price_including_vat') {
      const priceIncl = parseFloat(value) || 0;
      line.unit_price_excluding_vat = (priceIncl / vatMultiplier).toFixed(2);
      line.total_excluding_vat = ((priceIncl / vatMultiplier) * quantity).toFixed(2);
      line.total_including_vat = (priceIncl * quantity).toFixed(2);
    } else if (field === 'vat') {
      // When VAT changes, recalculate based on excl. VAT price
      const priceExcl = parseFloat(line.unit_price_excluding_vat) || 0;
      line.unit_price_including_vat = (priceExcl * vatMultiplier).toFixed(2);
      line.total_excluding_vat = (priceExcl * quantity).toFixed(2);
      line.total_including_vat = (priceExcl * quantity * vatMultiplier).toFixed(2);
    } else if (field === 'quantity') {
      // When quantity changes, recalculate totals
      const priceExcl = parseFloat(line.unit_price_excluding_vat) || 0;
      line.total_excluding_vat = (priceExcl * quantity).toFixed(2);
      line.total_including_vat = (priceExcl * quantity * vatMultiplier).toFixed(2);
    }
    
    updatedLines[index] = line;
    setInvoiceLines(updatedLines);
    setHasUnsavedChanges(true);
  };

  const handleSaveLines = async () => {
    setSavingLines(true);
    setError(null);
    
    try {
      const response = await axios.put(`${API_BASE_URL}/api/invoices/${id}/lines/update`, {
        lines: invoiceLines
      }, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      
      if (response.data.success) {
        setSuccessMessage('Laskurivit tallennettu onnistuneesti');
        setHasUnsavedChanges(false);
        setTimeout(() => setSuccessMessage(null), 3000);
      }
    } catch (err) {
      console.error('Error saving invoice lines:', err);
      setError(err.response?.data?.message || 'Virhe laskurivien tallentamisessa');
    } finally {
      setSavingLines(false);
    }
  };

  const handleCancel = () => {
    history.goBack();
  };

 

  if (loading) {
    return (
      <div className="container mt-5">
        <div className="text-center">
          <Spinner animation="border" />
          <p className="mt-2">{strings.loading}</p>
        </div>
      </div>
    );
  }

  if (error && !invoice) {
    return (
      <div className="container mt-5">
        <Alert variant="danger">{error}</Alert>
        <Button variant="secondary" onClick={() => history.goBack()}>
          {strings.cancel}
        </Button>
      </div>
    );
  }

  return (
    <div className="container mt-4">
      <h3>{strings.editInvoice}</h3>
      
      {successMessage && <Alert variant="success">{successMessage}</Alert>}
      {error && <Alert variant="danger">{error}</Alert>}
      
      <Form onSubmit={handleSubmit}>
          <Form.Group className="mb-3">
            <Form.Label>{strings.searchCustomer}</Form.Label>
            {selectedCustomer ? (
              <div className="alert alert-info d-flex justify-content-between align-items-center">
                <div>
                  <strong>{selectedCustomer.name}</strong>
                  {selectedCustomer.business_id && <span className="ms-2">({selectedCustomer.business_id})</span>}
                  <br />
                  <small>{selectedCustomer.email}</small>
                </div>
                <Button 
                  variant="outline-secondary" 
                  size="sm"
                  onClick={() => {
                    setSelectedCustomer(null);
                    setFormData(prev => ({...prev, customer_id: ''}));
                  }}
                >
                  Vaihda
                </Button>
              </div>
            ) : (
              <>
                <Form.Control
                  type="text"
                  placeholder={strings.searchPlaceholder}
                  value={customerSearch}
                  onChange={handleCustomerSearch}
                />
                {customers.length > 0 && (
                  <div className="mt-2" style={{maxHeight: '300px', overflowY: 'auto'}}>
                    <Table className='EditCustomers-Table-Search' striped bordered hover size="sm">
                      <thead>
                        <tr>
                          <th>{strings.name}</th>
                          <th>{strings.businessId}</th>
                          <th>{strings.email}</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        {customers.map((customer) => (
                          <tr key={customer.id}>
                            <td>{customer.name}</td>
                            <td>{customer.business_id || '-'}</td>
                            <td>{customer.email || '-'}</td>
                            <td>
                              <Button 
                                variant="primary" 
                                size="sm"
                                onClick={() => handleSelectCustomer(customer)}
                              >
                                {strings.selectCustomer}
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  </div>
                )}
                {customerSearch.length >= 2 && customers.length === 0 && (
                  <div className="text-muted mt-2">{strings.noCustomersFound}</div>
                )}
              </>
            )}
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.invoiceNumber}</Form.Label>
            <Form.Control
              type="text"
              name="invoice_number"
              value={formData.invoice_number}
              readOnly
              disabled
              style={{backgroundColor: '#e9ecef', cursor: 'not-allowed'}}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.buyers_reference}</Form.Label>
            <Form.Control
              type="text"
              name="buyers_reference"
              value={formData.buyers_reference}
              onChange={handleInputChange}
              style={{backgroundColor: '#e9ecef', cursor: 'pointer'}}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.sellers_reference}</Form.Label>
            <Form.Control
              type="text"
              name="sellers_reference"
              value={formData.sellers_reference}
              onChange={handleInputChange}
              style={{backgroundColor: '#e9ecef', cursor: 'pointer'}}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.reference_code}</Form.Label>
            <Form.Control
              type="text"
              name="invoice_number"
              value={formData.reference_code || ''}
              readOnly
              disabled
              style={{backgroundColor: '#e9ecef', cursor: 'not-allowed'}}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.sepa_reference}</Form.Label>
            <Form.Control
              type="text"
              name="sepa_reference"
              value={formData.sepa_reference || ''}
              readOnly
              disabled
              style={{backgroundColor: '#e9ecef', cursor: 'not-allowed'}}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.paymentTerm}</Form.Label>
            <Form.Select
              name="payment_term_id"
              value={formData.payment_term_id || ''}
              onChange={handleInputChange}
              required
              disabled={isLoadingPaymentTerms}
            >
              {isLoadingPaymentTerms ? (
                <option>Loading...</option>
              ) : (
                paymentTerms.map((term) => (
                  <option key={term.id} value={term.id}>
                    {getPaymentTermLabel(term)}
                  </option>
                ))
              )}
            </Form.Select>
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.invoiceDate}</Form.Label>
            <Form.Control
              type="date"
              name="invoice_date"
              value={formData.invoice_date}
              onChange={handleInputChange}
              required
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.dueDate}</Form.Label>
            <Form.Control
              type="date"
              name="due_date"
              value={formData.due_date}
              onChange={handleInputChange}
              required
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.complaints_within}</Form.Label>
            <Form.Control
              type="text"
              name="complaints_within"
              value={formData.complaints_within}
              onChange={handleInputChange}
              style={{backgroundColor: '#e9ecef', cursor: 'pointer'}}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>{strings.status}</Form.Label>
            <Form.Select
              name="status"
              value={formData.status}
              onChange={handleInputChange}
              required
            >
              <option value="draft">{strings.draft}</option>
              <option value="open">{strings.open}</option>
              <option value="paid">{strings.paid}</option>
              <option value="partial">{strings.partial}</option>
              <option value="overdue">{strings.overdue}</option>
              <option value="overpaid">{strings.overpaid}</option>
            </Form.Select>
          </Form.Group>

          <div className="mt-4">
            <h5>{strings.invoiceLines}</h5>
            <Table className='Invoice-Lines' striped bordered hover responsive>
              <thead>
                <tr>
                  <th>#</th>
                  <th>{strings.description}</th>
                  <th>{strings.quantity}</th>
                  <th>{strings.unit}</th>
                  <th>{strings.unitPriceExclVat}</th>
                  <th>{strings.unitPriceInclVat}</th>
                  <th>{strings.vatRate}</th>
                  <th>{strings.totalExclVat}</th>
                  <th>{strings.totalInclVat}</th>
                  <th>{strings.actions}</th>
                </tr>
              </thead>
              <tbody>
                {invoiceLines.length > 0 ? (
                  invoiceLines.map((line, index) => {
                    return (
                      <tr key={line.id || index}>
                        <td>{index + 1}</td>
                        <td>
                          <Form.Control
                            type="text"
                            size="sm"
                            value={line.description || ''}
                            onChange={(e) => handleLineChange(index, 'description', e.target.value)}
                          />
                        </td>
                        <td>
                          <Form.Control
                            type="number"
                            size="sm"
                            style={{width: '60px'}}
                            value={line.quantity || 0}
                            onChange={(e) => handleLineChange(index, 'quantity', e.target.value)}
                          />
                        </td>
                        <td>
                          <Form.Control
                            type="text"
                            size="sm"
                            style={{width: '60px'}}
                            value={line.unit || 't'}
                            onChange={(e) => handleLineChange(index, 'unit', e.target.value)}
                          />
                        </td>
                        <td>
                          <Form.Control
                            type="number"
                            size="sm"
                            step="0.01"
                            style={{width: '100px'}}
                            value={line.unit_price_excluding_vat || 0}
                            onChange={(e) => handleLineChange(index, 'unit_price_excluding_vat', e.target.value)}
                          />
                        </td>
                        <td>
                          <Form.Control
                            type="number"
                            size="sm"
                            step="0.01"
                            style={{width: '100px'}}
                            value={line.unit_price_including_vat || 0}
                            onChange={(e) => handleLineChange(index, 'unit_price_including_vat', e.target.value)}
                          />
                        </td>
                        <td>
                          <Form.Control
                            type="number"
                            size="sm"
                            style={{width: '80px'}}
                            value={line.vat || 0}
                            onChange={(e) => handleLineChange(index, 'vat', e.target.value)}
                          />
                        </td>
                        <td>{parseFloat(line.total_excluding_vat || 0).toFixed(2)} €</td>
                        <td>{parseFloat(line.total_including_vat || 0).toFixed(2)} €</td>
                        <td>
                          <Button 
                            variant="danger" 
                            size="sm"
                            onClick={() => {
                              
                            }}
                          >
                            {strings.delete}
                          </Button>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan="10" className="text-center">
                      {strings.noLines}
                    </td>
                  </tr>
                )}
              </tbody>
            </Table>
            <div className="d-flex justify-content-end mb-3">
              <strong>Rivien summa: {invoiceTotal.toFixed(2)} €</strong>
            </div>
            <div className="d-flex gap-2 mb-3">
              <Button variant="success" size="sm" onClick={handleAddLine}>
                {strings.addLine}
              </Button>
              <Button 
                variant="primary" 
                size="sm" 
                onClick={handleSaveLines}
                disabled={!hasUnsavedChanges || savingLines}
              >
                {savingLines ? 'Tallennetaan...' : 'Tallenna rivit'}
              </Button>
            </div>
          </div>

          <div className="d-flex justify-content-end gap-2 mt-4">
            <Button variant="secondary" onClick={handleCancel} disabled={saving}>
              {strings.cancel}
            </Button>
            <Button variant="primary" type="submit" disabled={saving}>
              {saving ? strings.loading : strings.save}
            </Button>
          </div>
        </Form>
      </div>
  );
};

export default withRouter(EditInvoice);