import React, { useEffect, useState } from "react";
import { useParams, useHistory, withRouter } from "react-router-dom";
import axios from "axios";
import { Button, Form, Spinner } from "react-bootstrap";
import { API_BASE_URL, ACCESS_TOKEN_NAME, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import PermissionGate from "../../contexts/PermissionGate";
import LocalizedStrings from "react-localization";

// Lokalisaatio
let strings = new LocalizedStrings({
  en: {
    title: "Edit Customer",
    save: "Save",
    cancel: "Cancel",
    error_fetching: "Error fetching customer data",
    success_update: "Customer updated successfully",
    phone_number: "Phone number",
    business_id: "Business ID",
    vat_id: "VAT ID",
    address_line_1: "Address line 1",
    address_line_2: "Address line 2",
    zip: "ZIP code",
    city: "City",
    name: "Name",
    contact_person_name: "Contact Person Name",
    email: "Email",
  },
  fi: {
    title: "Muokkaa asiakasta",
    save: "Tallenna",
    cancel: "Peruuta",
    error_fetching: "Virhe haettaessa asiakastietoja",
    success_update: "Asiakas päivitetty onnistuneesti",
    phone_number: "Puhelinnumero",
    business_id: "Y-tunnus",
    vat_id: "ALV-tunnus",
    address_line_1: "Osoite 1",
    address_line_2: "Osoite 2",
    zip: "Postinumero",
    city: "Kaupunki",
    name: "Nimi",
    contact_person_name: "Yhteyshenkilön Nimi",
    email: "Sähköposti",
  },
  sv: {
    title: "Redigera kund",
    save: "Spara",
    cancel: "Avbryt",
    error_fetching: "Fel vid hämtning av kunddata",
    success_update: "Kunden uppdaterades framgångsrikt",
    phone_number: "Telefonnummer",
    business_id: "Org. nr",
    vat_id: "VAT ID",
    address_line_1: "Adress 1",
    address_line_2: "Adress 2",
    zip: "Postnummer",
    city: "Stad",
    name: "Namn",
    contact_person_name: "Kontaktpersonens Namn",
    email: "E-post",
  },
});

function EditCustomer() {
  const { id } = useParams();
  const navigate =  useHistory();
  const [customer, setCustomer] = useState({});
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    // Hae kieliasetus selaimesta tai oletusarvo
    const lang = localStorage.getItem("lang") || API_DEFAULT_LANGUAGE;
    strings.setLanguage(lang);

    // Hae asiakkaan tiedot
    axios
      .get(`${API_BASE_URL}/api/customers/show/${id}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem(ACCESS_TOKEN_NAME)}` },
      })
      .then((res) => {
        setCustomer(res.data.data);
        setLoading(false);
      })
      .catch(() => {
        setError(strings.error_fetching);
        setLoading(false);
      });
  }, [id]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setCustomer((prev) => ({ ...prev, [name]: value }));
  };

  const handleSave = () => {
    setLoading(true);
    axios
      .put(`${API_BASE_URL}/api/customers/update/${id}`, customer, {
        headers: { Authorization: `Bearer ${localStorage.getItem(ACCESS_TOKEN_NAME)}` },
      })
      .then((res) => {
        setMessage(strings.success_update);
        setLoading(false);
        setTimeout(() => {
          navigate.push("/customers"); // Paluu asiakaslistalle
        }, 1500);
      })
      .catch(() => {
        setError(strings.error_fetching);
        setLoading(false);
      });
  };

    if (loading) {
      return (
        <div className="text-center mt-4">
          <Spinner animation="border" />
        </div>
      );
    }

  return (
    <PermissionGate permission={"customers.edit"}>
    <div className="container mt-4">
      <h3>{strings.title}</h3>
      {message && <div className="alert alert-success">{message}</div>}
      {error && <div className="alert alert-danger">{error}</div>}

      <Form>
        <Form.Group className="mb-3">
          <Form.Label>{strings.name}</Form.Label>
          <Form.Control
            type="text"
            name="name"
            value={customer.name || ""}
            onChange={handleChange}
          />
        </Form.Group>

      <Form.Group className="mb-3">
        <Form.Label>{strings.contact_person_name}</Form.Label>
          <Form.Control
            type="text"
            name="contact_person_name"
            value={customer.contact_person_name || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.email}</Form.Label>
          <Form.Control
            type="email"
            name="email"
            value={customer.email || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.phone_number}</Form.Label>
          <Form.Control
            type="text"
            name="phone_number"
            value={customer.phone_number || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.business_id}</Form.Label>
          <Form.Control
            type="text"
            name="business_id"
            value={customer.business_id || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.vat_id}</Form.Label>
          <Form.Control
            type="text"
            name="vat_id"
            value={customer.vat_id || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.address_line_1}</Form.Label>
          <Form.Control
            type="text"
            name="address_line_1"
            value={customer.address_line_1 || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.address_line_2}</Form.Label>
          <Form.Control
            type="text"
            name="address_line_2"
            value={customer.address_line_2 || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.zip}</Form.Label>
          <Form.Control
            type="text"
            name="zip"
            value={customer.zip || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>{strings.city}</Form.Label>
          <Form.Control
            type="text"
            name="city"
            value={customer.city || ""}
            onChange={handleChange}
          />
        </Form.Group>

        <Button variant="primary" onClick={handleSave} className="me-2">
          {strings.save}
        </Button>
        <Button variant="secondary" onClick={() => navigate.push("/customers")}>
          {strings.cancel}
        </Button>
      </Form>
    </div>
    </PermissionGate>
  );
}

export default withRouter(EditCustomer);
