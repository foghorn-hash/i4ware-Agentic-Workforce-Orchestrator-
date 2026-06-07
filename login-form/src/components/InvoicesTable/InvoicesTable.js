import React, { useEffect, useState } from 'react';
import { Table, Spinner, Alert, Button, Modal, Form } from 'react-bootstrap';
import axios from 'axios';
import "./InvoicesTable.css";
import { useParams, useHistory, withRouter } from "react-router-dom";
import { Pencil, Trash, Download } from "react-bootstrap-icons";
import { API_BASE_URL, ACCESS_TOKEN_NAME, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import { getPaymentTermLabel } from "../../utils/paymentTerms";
import {AuthContext} from "../../contexts/auth.contexts";
import PermissionGate from "../../contexts/PermissionGate";
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en: {
    title: "Sales Invoices",
    id: "ID",
    customer: "Customer",
    invoiceNumber: "Invoice Number",
    referenceCode: "Reference Code",
    invoiceDate: "Invoice Date",
    dueDate: "Due Date",
    status: "Status",
    createdAt: "Created At",
    updatedAt: "Updated At",
    error_fetching_invoices: "Error fetching invoices",
    noInvoicesFound: "No invoices found.",
    previous: "Previous",
    next: "Next",
    pageOf: "Page {current} of {total}",
    addInvoice: "Add Invoice",
    success_adding_invoice: "Invoice added successfully",
    error_adding_invoice: "Error adding invoice",
    cancel: "Cancel",
    save: "Save",
    customerId: "Customer ID",
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
    actions: "Actions",
    edit: "Edit",
    download: "Download",
    paymentTerm: "Payment Term",
    sellers_reference: "Seller's Reference",
    buyers_reference: "Buyer's Reference",
    complaints_within: "Complaints Within"
  },
  fi: {
    title: "Myyntilaskut",
    id: "ID",
    customer: "Asiakas",
    invoiceNumber: "Laskunumero",
    referenceCode: "Viitenumero",
    invoiceDate: "Laskun päivämäärä",
    dueDate: "Eräpäivä",
    status: "Tila",
    createdAt: "Luotu",
    updatedAt: "Päivitetty",
    error_fetching_invoices: "Virhe haettaessa laskuja",
    noInvoicesFound: "Laskuja ei löytynyt.",
    previous: "Edellinen",
    next: "Seuraava",
    pageOf: "Sivu {current} / {total}",
    addInvoice: "Lisää lasku",
    success_adding_invoice: "Lasku lisätty onnistuneesti",
    error_adding_invoice: "Virhe laskun lisäämisessä",
    cancel: "Peruuta",
    save: "Tallenna",
    customerId: "Asiakas ID",
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
    actions: "Toiminnot",
    edit: "Muokkaa",
    download: "Lataa",
    paymentTerm: "Maksuehto",
    sellers_reference: "Myyjän viite",
    buyers_reference: "Ostajan viite",
    complaints_within: "Huomautusaika"
  },
  sv: {
    title: "Försäljningsfakturor",
    id: "ID",
    customer: "Kund",
    invoiceNumber: "Fakturanummer",
    referenceCode: "Referenskod",
    invoiceDate: "Fakturadatum",
    dueDate: "Förfallodatum",
    status: "Status",
    createdAt: "Skapad",
    updatedAt: "Uppdaterad",
    error_fetching_invoices: "Fel vid hämtning av fakturor",
    noInvoicesFound: "Inga fakturor hittades.",
    previous: "Föregående",
    next: "Nästa",
    pageOf: "Sida {current} av {total}",
    addInvoice: "Lägg till faktura",
    success_adding_invoice: "Faktura tillagd",
    error_adding_invoice: "Fel vid tillägg av faktura",
    cancel: "Avbryt",
    save: "Spara",
    customerId: "Kund ID",
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
    actions: "Åtgärder",
    edit: "Redigera",
    download: "Ladda ner",
    paymentTerm: "Betalningsvillkor",
    sellers_reference: "Säljarens referens",
    buyers_reference: "Köparens referens",
    complaints_within: "Reklamation inom"
  },
});

const InvoicesTable = () => {
  const {authState, authActions} = React.useContext(AuthContext);
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [paymentTerms, setPaymentTerms] = useState([]);
  const [isLoadingPaymentTerms, setIsLoadingPaymentTerms] = useState(false);
  const [error, setError] = useState(null);
  const [showModal, setShowModal] = useState(false);
  
  const [formData, setFormData] = useState({
    customer_id: '',
    invoice_number: '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: (() => {
      const today = new Date();
      today.setDate(today.getDate() + 1);
      return today.toISOString().split('T')[0];
    })(),
    payment_term: null,
    payment_term_id: null,
    status: 'draft'
  });
  const [customers, setCustomers] = useState([]);
  const [customerSearch, setCustomerSearch] = useState('');
  const [selectedCustomer, setSelectedCustomer] = useState(null);

  const hasActionsPermission = authState?.user?.permissions?.includes("invoices.actions");
  const colSpanValue = hasActionsPermission ? 10 : 9;

  var query = window.location.search.substring(1);
  var urlParams = new URLSearchParams(query);
  var localization = urlParams.get('lang');

  if (localization===null) {
    strings.setLanguage(API_DEFAULT_LANGUAGE);
  } else {
    strings.setLanguage(localization);
  }

  const [pagination, setPagination] = useState({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
   });

  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchInvoices();
  }, []);

  useEffect(() => {
    if (showModal) {
      fetchNextInvoiceNumber();
    }
  }, [showModal]);

  // Debounced search: whenever searchQuery changes, reload page 1
  useEffect(() => {
    const handler = setTimeout(() => {
      fetchInvoices(1);
    }, 400);

    return () => clearTimeout(handler);
  }, [searchQuery]);

  const fetchInvoices = async (page = 1) => {
        setLoading(true);
        setError(null);
        try {
            // Build query string including optional search query
            const params = new URLSearchParams();
            params.append('page', page);
            if (searchQuery && searchQuery.trim() !== '') {
              params.append('q', searchQuery.trim());
            }

            const response = await axios.get(`${API_BASE_URL}/api/invoices/index?` + params.toString(), {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
            });

            setInvoices(response.data.data || []);
            setPagination(response.data.pagination || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
        } catch (err) {
            setError(strings.error_fetching_invoices || 'Error fetching Invoices');
        } finally {
            setLoading(false);
        }
  };

  const fetchNextInvoiceNumber = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/invoices/next-invoice-number`, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      setFormData(prev => ({
        ...prev,
        invoice_number: response.data.next_invoice_number
      }));
    } catch (err) {
      console.error('Error fetching next invoice number:', err);
    }
  };

  const addInvoice = async (invoiceData) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/api/invoices/store`, invoiceData, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
      });
      
      if (response.data) {
        // Päivitä laskut listaus
        fetchInvoices(pagination.current_page);
        setShowModal(false);
        const today = new Date();
        const firstTerm = paymentTerms.length > 0 ? paymentTerms[0] : null;
        const firstTermDays = firstTerm ? parseInt(firstTerm.days_to_pay) : 14;
        const defaultDueDate = new Date(today);
        defaultDueDate.setDate(defaultDueDate.getDate() + firstTermDays);
        
        setFormData({
          customer_id: '',
          invoice_number: '',
          invoice_date: today.toISOString().split('T')[0],
          due_date: defaultDueDate.toISOString().split('T')[0],
          payment_term: firstTerm ? firstTerm.id : null,
          payment_term_id: firstTerm ? firstTerm.id : null,
          sellers_reference: '',
          buyers_reference: '',
          complaints_within: '',
          status: 'draft'
        });
        return { success: true, message: strings.success_adding_invoice };
      }
    } catch (err) {
      console.error('Error adding invoice:', err);
      return { 
        success: false, 
        message: err.response?.data?.message || strings.error_adding_invoice 
      };
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    // Jos muutetaan laskupäivämäärää tai maksuehtoa, laske eräpäivä automaattisesti
    if (name === 'invoice_date' || name === 'payment_term') {
      const invoiceDate = name === 'invoice_date' ? value : formData.invoice_date;
      
      if (name === 'payment_term') {
        // Etsi payment term ID:llä ja hae days_to_pay
        const selectedTerm = paymentTerms.find(term => String(term.id) === String(value));
        const paymentTermDays = selectedTerm ? parseInt(selectedTerm.days_to_pay) : 0;
        
        if (invoiceDate && paymentTermDays && selectedTerm) {
          const dueDate = new Date(invoiceDate);
          dueDate.setDate(dueDate.getDate() + paymentTermDays);
          
          setFormData(prev => ({
            ...prev,
            payment_term: value,
            payment_term_id: value,
            due_date: dueDate.toISOString().split('T')[0]
          }));
          return;
        }
      } else {
        // invoice_date muuttui - etsi days_to_pay formData.payment_term ID:llä
        const selectedTerm = paymentTerms.find(term => String(term.id) === String(formData.payment_term));
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

  const handleSubmit = async (e) => {
    e.preventDefault();
    await addInvoice(formData);
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
          
          // Aseta ensimmäinen maksuehto oletukseksi
          if (terms.length > 0 && terms[0].days_to_pay) {
            const firstTerm = terms[0];
            const today = new Date();
            const dueDate = new Date(today.getTime());
            const daysToAdd = parseInt(firstTerm.days_to_pay);
            
            if (!isNaN(daysToAdd)) {
              dueDate.setDate(dueDate.getDate() + daysToAdd);
              
              setFormData(prev => ({
                ...prev,
                payment_term: firstTerm.id,
                payment_term_id: firstTerm.id,
                due_date: dueDate.toISOString().split('T')[0]
              }));
            }
          }
        } catch (err) {
          console.error("Could not fetch payment terms:", err);
        } finally {
          setIsLoadingPaymentTerms(false);
        }
      };
  
      fetchPaymentTerms();
  
    }, []);

  const navigate = useHistory();
  const handleEditInvoice = (invoiceId) => {
    navigate.push(`/invoice/edit/${invoiceId}`);
  };

  const handleDownloadInvoice = async (invoiceId) => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/invoices/${invoiceId}/download`, {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) },
        responseType: 'blob'
      });
      
      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `invoice_${invoiceId}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.parentNode.removeChild(link);
    } catch (err) {
      console.error('Error downloading invoice:', err);
      alert('Error downloading invoice');
    }
  };

  if (loading) {
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
  <PermissionGate permission={"invoices.view"} >
    <div className="mt-4">
    <div className="d-flex justify-content-between align-items-center mb-3">
      <h3>{strings.title}</h3>
      <div className="d-flex align-items-center gap-2">
        <input
          type="text"
          className="form-control"
          placeholder="Search invoices or customers"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          style={{ maxWidth: '360px' }}
        />
        {searchQuery && (
          <Button variant="outline-secondary" size="sm" onClick={() => setSearchQuery('')}>
            Clear
          </Button>
        )}
        <PermissionGate permission={"invoices.add"}>
          <Button className='add-invoice' variant="primary" onClick={() => setShowModal(true)}>
            {strings.addInvoice}
          </Button>
        </PermissionGate>
      </div>
    </div>
    <Table className='InvoicesTable' striped bordered hover responsive>
      <thead>
        <tr>
          <th>{strings.id}</th>
          <th>{strings.customer}</th>
          <th>{strings.invoiceNumber}</th>
          <th>{strings.referenceCode}</th>
          <th>{strings.invoiceDate}</th>
          <th>{strings.dueDate}</th>
          <th>{strings.status}</th>
          <th>{strings.createdAt}</th>
          <th>{strings.updatedAt}</th>
          <PermissionGate permission={"invoices.actions"}><th>{strings.actions}</th></PermissionGate>
        </tr>
      </thead>
      <tbody>
        {invoices.length > 0 ? (
          invoices.map((invoice, index) => (
            <tr key={invoice.id} className={index % 2 === 0 ? 'invoices-row-even' : 'invoices-row-odd'}>
              <td className="text-center">{invoice.id}</td>
              <td className="text-center">{invoice.customer?.name || invoice.customer_id}</td>
              <td className="text-center">{invoice.invoice_number}</td>
              <td className="text-center">{invoice.reference_code}</td>
              <td className="text-center">{invoice.invoice_date}</td>
              <td className="text-center">{invoice.due_date}</td>
              <td className="text-center">{invoice.status}</td>
              <td className="text-center">{new Date(invoice.created_at).toLocaleString()}</td>
              <td className="text-center">{new Date(invoice.updated_at).toLocaleString()}</td>
              <PermissionGate permission={"invoices.actions"}>
                  <td className="text-center">
                      <PermissionGate permission={"invoices.edit"}>
                      {/* Edit button */}
                      <button
                      className="btn btn-sm btn-primary me-2"
                      onClick={() => handleEditInvoice(invoice.id)}
                      title={strings.edit}
                      >
                          <Pencil />
                      </button>
                      </PermissionGate>          
                      {/* Download button */}
                      <button
                      className="btn btn-sm btn-success me-2"
                      onClick={() => handleDownloadInvoice(invoice.id)}
                      title={strings.download}
                      >
                          <Download />
                      </button>
                  </td>
              </PermissionGate>
            </tr>
          ))
        ) : (
          <tr>
            <td colSpan={colSpanValue} className="text-center">
              {strings.noInvoicesFound}
            </td>
          </tr>
        )}
      </tbody>
    </Table>
    </div>
    <div className="d-flex justify-content-between mt-3">
        <button
            className="btn btn-secondary"
            disabled={pagination.current_page <= 1}
            onClick={() => fetchInvoices(pagination.current_page - 1)}
        >
            {strings.previous}
        </button>
        <span>
          {strings.pageOf
          .replace("{current}", pagination.current_page)
          .replace("{total}", pagination.last_page)}
        </span>
        <button
            className="btn btn-secondary"
            disabled={pagination.current_page >= pagination.last_page}
            onClick={() => fetchInvoices(pagination.current_page + 1)}
        >
            {strings.next}
        </button>
    </div>

    <Modal show={showModal} onHide={() => setShowModal(false)} size="lg">
      <Modal.Header closeButton>
        <Modal.Title>{strings.addInvoice}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
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
                    <Table striped bordered hover size="sm">
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
            <Form.Label>{strings.paymentTerm}</Form.Label>
            <Form.Select
              name="payment_term"
              value={formData.payment_term}
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

          <div className="d-flex justify-content-end gap-2">
            <Button variant="secondary" onClick={() => setShowModal(false)}>
              {strings.cancel}
            </Button>
            <Button variant="primary" type="submit">
              {strings.save}
            </Button>
          </div>
        </Form>
      </Modal.Body>
    </Modal>
  </PermissionGate>
  );
};

export default withRouter(InvoicesTable);
