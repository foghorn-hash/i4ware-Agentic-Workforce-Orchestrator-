import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { ACCESS_TOKEN_NAME, API_BASE_URL, API_DEFAULT_LANGUAGE } from '../../constants/apiConstants';
import { Button, Form, Table } from 'react-bootstrap';
import './PurchaseOrders.css';

function getAuthHeaders() {
  const token = localStorage.getItem(ACCESS_TOKEN_NAME);
  return token ? { Authorization: 'Bearer ' + token } : {};
}

export default function PurchaseOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [form, setForm] = useState({ order_number: '', order_date: '', total_amount: '', status: 'draft' });

  useEffect(() => {
    load();
  }, []);

  function load() {
    setLoading(true);
    axios.get(`${API_BASE_URL}/api/purchase-orders/index`, { headers: getAuthHeaders() })
      .then(res => {
        setOrders(res.data.data || []);
      }).catch(err => {
        console.error(err);
      }).finally(() => setLoading(false));
  }

  function handleChange(e) {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    axios.post(`${API_BASE_URL}/api/purchase-orders/store`, form, { headers: getAuthHeaders() })
      .then(res => {
        setForm({ order_number: '', order_date: '', total_amount: '', status: 'draft' });
        load();
      }).catch(err => {
        console.error(err);
        alert('Error creating purchase order');
      });
  }

  return (
    <div>
      <h2>Purchase Orders</h2>
      <div>
        <Form onSubmit={handleSubmit} className="mb-3">
          <Form.Group controlId="order_number">
            <Form.Label>Order number</Form.Label>
            <Form.Control name="order_number" value={form.order_number} onChange={handleChange} required />
          </Form.Group>
          <Form.Group controlId="order_date">
            <Form.Label>Order date</Form.Label>
            <Form.Control type="date" name="order_date" value={form.order_date} onChange={handleChange} />
          </Form.Group>
          <Form.Group controlId="total_amount">
            <Form.Label>Total amount</Form.Label>
            <Form.Control type="number" step="0.01" name="total_amount" value={form.total_amount} onChange={handleChange} />
          </Form.Group>
          <Form.Group controlId="status">
            <Form.Label>Status</Form.Label>
            <Form.Control name="status" value={form.status} onChange={handleChange} />
          </Form.Group>
          <Button type="submit" className="mt-2">Create</Button>
        </Form>
      </div>

      <div>
        {loading ? <p>Loading…</p> : (
          <Table striped bordered hover className="purchase-orders-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Order number</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {orders.map(o => (
                <tr key={o.id} className={`purchase-order-${o.id} ${o.status === 'draft' ? 'purchase-order-draft' : ''}`} onClick={() => alert(`Order #${o.id}\nNumber: ${o.order_number}\nDate: ${o.order_date}\nTotal: ${o.total_amount}\nStatus: ${o.status}`)}>
                  <td>{o.id}</td>
                  <td>{o.order_number}</td>
                  <td>{o.order_date}</td>
                  <td>{o.total_amount}</td>
                  <td>{o.status}</td>
                </tr>
              ))}
            </tbody>
          </Table>
        )}
      </div>
    </div>
  );
}
