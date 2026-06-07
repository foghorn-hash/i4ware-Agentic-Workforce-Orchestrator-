import React, { useEffect, useState } from "react";
import "./CustomersTable.css";
import { API_BASE_URL, ACCESS_TOKEN_NAME, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import {AuthContext} from "../../contexts/auth.contexts";
import PermissionGate from "../../contexts/PermissionGate";
import Table from "react-bootstrap/Table";
import Spinner from "react-bootstrap/Spinner";
import Alert from "react-bootstrap/Alert";
import { Button, Modal, Form } from "react-bootstrap";
import { Pencil, Trash } from "react-bootstrap-icons";
import { useHistory, withRouter } from "react-router-dom";
import axios from 'axios';

import LocalizedStrings from "react-localization";

let strings = new LocalizedStrings({
  en: {
    title: "Customers",
    addCustomer: "Add Customer",
    id: "ID",
    name: "Name",
    contact_person_name: "Contact Person Name",
    email: "Email",
    phone: "Phone",
    businessId: "Business ID",
    vatId: "VAT ID",
    address1: "Address Line 1",
    address2: "Address Line 2",
    zip: "ZIP",
    city: "City",
    domain: "Domain",
    createdAt: "Created At",
    updatedAt: "Updated At",
    noCustomers: "No customers found",
    previous: "Previous",
    next: "Next",
    pageOf: "Page {current} of {total}",
    modalTitle: "Add New Customer",
    save: "Save",
    cancel: "Cancel",
    namePlaceholder: "Enter customer name",
    contact_person_namePlaceholder: "Enter contact person name",
    emailPlaceholder: "Enter email",
    phone_numberPlaceholder: "Enter phone number",
    business_idPlaceholder: "Enter business ID",
    vat_idPlaceholder: "Enter VAT ID",
    address_line_1Placeholder: "Enter address line 1",
    address_line_2Placeholder: "Enter address line 2",
    zipPlaceholder: "Enter ZIP code",
    cityPlaceholder: "Enter city",
    phone_number: "Phone number",
    business_id: "Business ID",
    vat_id: "VAT ID",
    address_line_1: "Address line 1",
    address_line_2: "Address line 2",
    zip: "ZIP code",
    city: "City",
    actions: "Actions",
    edit: "Edit",
    delete: "Delete",
    confirmDeleteTitle: "Confirm Deletion",
    confirmDeleteMessage: "Are you sure you want to delete this customer?",
    yesDelete: "Yes, Delete",
    search: "Search",
    searchPlaceholder: "Search by name, business ID, or email..."
  },
  fi: {
    title: "Asiakkaat",
    addCustomer: "Lisää asiakas",
    id: "ID",
    name: "Nimi",
    contact_person_name: "Yhteyshenkilön Nimi",
    email: "Sähköposti",
    phone: "Puhelin",
    businessId: "Y-tunnus",
    vatId: "ALV-tunnus",
    address1: "Osoite 1",
    address2: "Osoite 2",
    zip: "Postinumero",
    city: "Kaupunki",
    domain: "Domain",
    createdAt: "Luotu",
    updatedAt: "Päivitetty",
    noCustomers: "Asiakkaita ei löytynyt",
    previous: "Edellinen",
    next: "Seuraava",
    pageOf: "Sivu {current} / {total}",
    modalTitle: "Lisää uusi asiakas",
    save: "Tallenna",
    cancel: "Peruuta",
    namePlaceholder: "Syötä asiakkaan nimi",
    contact_person_namePlaceholder: "Syötä yhteyshenkilön nimi",
    emailPlaceholder: "Syötä sähköposti",
    phone_numberPlaceholder: "Syötä puhelinnumero",
    business_idPlaceholder: "Syötä Y-tunnus",
    vat_idPlaceholder: "Syötä ALV-tunnus",
    address_line_1Placeholder: "Syötä osoite 1",
    address_line_2Placeholder: "Syötä osoite 2",
    zipPlaceholder: "Syötä postinumero",
    cityPlaceholder: "Syötä kaupunki",
    phone_number: "Puhelinnumero",
    business_id: "Y-tunnus",
    vat_id: "ALV-tunnus",
    address_line_1: "Osoite 1",
    address_line_2: "Osoite 2",
    zip: "Postinumero",
    city: "Kaupunki",
    actions: "Toiminnot",
    edit: "Muokkaa",
    delete: "Poista",
    confirmDeleteTitle: "Vahvista poisto",
    confirmDeleteMessage: "Haluatko varmasti poistaa tämän asiakkaan?",
    yesDelete: "Kyllä, poista",
    search: "Hae",
    searchPlaceholder: "Hae nimellä, Y-tunnuksella tai sähköpostilla..."
  },
  sv: {
    title: "Kunder",
    addCustomer: "Lägg till kund",
    id: "ID",
    name: "Namn",
    contact_person_name: "Kontaktpersonens Namn",
    email: "E-post",
    phone: "Telefon",
    businessId: "Org. nr",
    vatId: "VAT ID",
    address1: "Adress 1",
    address2: "Adress 2",
    zip: "Postnummer",
    city: "Stad",
    domain: "Domän",
    createdAt: "Skapad",
    updatedAt: "Uppdaterad",
    noCustomers: "Inga kunder hittades",
    previous: "Föregående",
    next: "Nästa",
    pageOf: "Sida {current} av {total}",
    modalTitle: "Lägg till ny kund",
    save: "Spara",
    cancel: "Avbryt",
    namePlaceholder: "Ange kundens namn",
    contact_person_namePlaceholder: "Ange kontaktpersonens namn",
    emailPlaceholder: "Ange e-post",
    phone_numberPlaceholder: "Ange telefonnummer",
    business_idPlaceholder: "Ange org. nr",
    vat_idPlaceholder: "Ange VAT ID",
    address_line_1Placeholder: "Ange adress 1",
    address_line_2Placeholder: "Ange adress 2",
    zipPlaceholder: "Ange postnummer",
    cityPlaceholder: "Ange stad",
    phone_number: "Telefonnummer",
    business_id: "Org. nr",
    vat_id: "VAT ID",
    address_line_1: "Adress 1",
    address_line_2: "Adress 2",
    zip: "Postnummer",
    city: "Stad",
    actions: "Åtgärder",
    edit: "Redigera",
    delete: "Radera",
    confirmDeleteTitle: "Bekräfta radering",
    confirmDeleteMessage: "Är du säker på att du vill radera denna kund?",
    yesDelete: "Ja, radera",
    search: "Sök",
    searchPlaceholder: "Sök efter namn, org. nr eller e-post..."
  }
});

function CustomersTable() {
  const {authState, authActions} = React.useContext(AuthContext);
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");

  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [customerToDelete, setCustomerToDelete] = useState(null);

  const handleDeleteCustomer = (id) => {
    setCustomerToDelete(id);
    setShowDeleteModal(true);
  };

  const confirmDeleteCustomer = async () => {
    try {
        await axios.delete(API_BASE_URL + `/api/customers/destroy/${customerToDelete}`, {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
            });
        setShowDeleteModal(false);
        setCustomerToDelete(null);
        fetchCustomers(); // päivitä taulukko
    } catch (error) {
        console.error("Delete failed", error);
    }
  };

  const [newCustomer, setNewCustomer] = useState({
    name: "",
    contact_person_name: "",
    email: "",
    phone_number: "",
    business_id: "",
    vat_id: "",
    address_line_1: "",
    address_line_2: "",
    zip: "",
    city: ""
  });

  const navigate = useHistory();
  const handleEditCustomer = (customerId) => {
    navigate.push(`/customer/edit/${customerId}`);
  };

  const [saving, setSaving] = useState(false);
  const hasActionsPermission = authState?.user?.permissions?.includes("customers.actions");
  const colSpanValue = hasActionsPermission ? 7 : 6;
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

  useEffect(() => {
    fetchCustomers();
  }, []);

  // Debounced search effect
  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchCustomers(1, searchTerm);
    }, 500);

    return () => clearTimeout(delayDebounce);
  }, [searchTerm]);

  const fetchCustomers = async (page = 1, search = "") => {
        try {
            let url = `${API_BASE_URL}/api/customers/index?page=${page}`;
            if (search && search.trim().length > 0) {
              url += `&search=${encodeURIComponent(search.trim())}`;
            }
            const response = await axios.get(url, {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem(ACCESS_TOKEN_NAME) }
            });

            setCustomers(response.data.data);
            setPagination(response.data.pagination);
            setLoading(false);
        } catch (err) {
            setError(strings.error_fetching_customers || 'Error fetching customers');
            setLoading(false);
        }
  };

  const onAddCustomerClick = () => {
        setShowModal(true);
    };

    const handleClose = () => {
        setShowModal(false);
        setNewCustomer({
        name: "",
        email: "",
        phone_number: "",
        business_id: "",
        vat_id: "",
        address_line_1: "",
        address_line_2: "",
        zip: "",
        city: ""
        });
    };

    const handleSave = async () => {
        setSaving(true);
        try {
        const response = await axios.post(`${API_BASE_URL}/api/customers/store`, newCustomer, {
            headers: { Authorization: "Bearer " + localStorage.getItem(ACCESS_TOKEN_NAME) }
        });
        if (response.data.success === true) {
            fetchCustomers();
            handleClose();
        }
        } catch (err) {
        console.error(err);
        alert("Error saving customer");
        } finally {
        setSaving(false);
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
    <PermissionGate permission={"customers.view"}>
        <div className="mt-4">
            <div className="d-flex justify-content-between align-items-center mb-3">
                <h3>{strings.title}</h3>
                <PermissionGate permission={"customers.add"}>
                    <Button className="add-customer" variant="primary" onClick={onAddCustomerClick}>
                        {strings.addCustomer}
                    </Button>
                </PermissionGate>
            </div>
            <div className="mb-3">
                <Form.Control
                    type="text"
                    placeholder={strings.searchPlaceholder}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="search-input"
                />
            </div>
        <Table className='CustomersTable' striped bordered hover responsive>
            <thead>
            <tr>
              <th>{strings.id}</th>
              <th>{strings.name}</th>
              <th>{strings.businessId}</th>
              <th>{strings.vatId}</th>
              <th>{strings.createdAt}</th>
              <th>{strings.updatedAt}</th>
              <PermissionGate permission={"customers.actions"}><th>{strings.actions}</th></PermissionGate>
            </tr>
            </thead>

            <tbody>
            {customers.length > 0 ? (
                customers.map((customer, index) => (
                    <tr key={customer.id} className={index % 2 === 0 ? 'customers-row-even' : 'customers-row-odd'}>
                        <td className="text-center">{customer.id}</td>
                        <td className="text-center">{customer.name}</td>
                        <td className="text-center">{customer.business_id}</td>
                        <td className="text-center">{customer.vat_id}</td>
                        <td className="text-center">{new Date(customer.created_at).toLocaleString()}</td>
                        <td className="text-center">{new Date(customer.updated_at).toLocaleString()}</td>
                        <PermissionGate permission={"customers.actions"}>
                            <td className="text-center">
                                <PermissionGate permission={"customers.edit"}>
                                {/* Edit button */}
                                <button
                                className="btn btn-sm btn-primary me-2"
                                 onClick={() => handleEditCustomer(customer.id)}
                                 title={strings.edit}
                                >
                                    <Pencil />
                                </button>
                                </PermissionGate>
                                {/* Destroy/Delete button */}
                                <PermissionGate permission={"customers.delete"}>
                                <button
                                 className="btn btn-sm btn-danger"
                                 onClick={() => handleDeleteCustomer(customer.id)}
                                 title={strings.delete}
                                >
                                    <Trash />
                                </button>
                                </PermissionGate>
                            </td>
                        </PermissionGate>
                    </tr>
                ))
                ) : (
                <tr>
                    <td colSpan={colSpanValue} className="text-center">
                        {strings.noCustomers}
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
                onClick={() => fetchCustomers(pagination.current_page - 1, searchTerm)}
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
                onClick={() => fetchCustomers(pagination.current_page + 1, searchTerm)}
            >
                {strings.next}
            </button>
        </div>
        <PermissionGate permission={"customers.add"}>
            <Modal show={showModal} onHide={handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>{strings.modalTitle}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        {Object.keys(newCustomer).map((field) => (
                            <Form.Group className="mb-2" key={field}>
                            <Form.Label>{strings[field] ?? field}</Form.Label>
                            <Form.Control
                                type="text"
                                placeholder={strings[`${field}Placeholder`] ?? ""}
                                value={newCustomer[field]}
                                onChange={(e) => setNewCustomer({ ...newCustomer, [field]: e.target.value })}
                            />
                            </Form.Group>
                        ))}
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>
                    {strings.cancel}
                    </Button>
                    <Button variant="primary" onClick={handleSave} disabled={saving}>
                    {strings.save}
                    </Button>
                </Modal.Footer>
            </Modal>
        </PermissionGate>
        <PermissionGate permission={"customers.delete"}>
            <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>{strings.confirmDeleteTitle}</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    {strings.confirmDeleteMessage}
                </Modal.Body>

                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
                        {strings.cancel}
                    </Button>

                    <Button variant="danger" onClick={confirmDeleteCustomer}>
                        {strings.yesDelete}
                    </Button>
                </Modal.Footer>
            </Modal>
        </PermissionGate>
    </PermissionGate>
  );
}

export default withRouter(CustomersTable);
