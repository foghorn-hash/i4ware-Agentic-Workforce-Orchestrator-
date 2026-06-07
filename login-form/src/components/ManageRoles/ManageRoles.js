import React, { useState, useContext, useEffect } from 'react';
import "./ManageRoles.css";
import { withRouter } from 'react-router-dom';
import { Button } from 'react-bootstrap';
import request from '../../utils/Request';
import { AuthContext } from '../../contexts/auth.contexts';
import Spinner from "react-bootstrap/Spinner";
import Alert from "react-bootstrap/Alert";
import { API_DEFAULT_LANGUAGE } from '../../constants/apiConstants';
import { useTranslation } from "react-i18next";


function ManageRoles(props) {
  const { t, i18n } = useTranslation();
  const {authState, authActions} = React.useContext(AuthContext);
  const [roles, setRoles] = useState([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [hasPrevious, setHasPrevious] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
 // Sync language from URL
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const langFromUrl = urlParams.get("lang");
    if (langFromUrl && ["en", "fi", "sv"].includes(langFromUrl)) {
      i18n.changeLanguage(langFromUrl);
    }
  }, [i18n]);

  useEffect(() => {
    fetchRoles(1);
  }, [searchQuery]);

  const fetchRoles = (pageNum) => {
    if (isLoading) return;

    setIsLoading(true);
    const params = new URLSearchParams();
    params.append('page', pageNum);
    if (searchQuery) {
      params.append('search', searchQuery);
    }
    request().get(`/api/manage/roles?${params}`)
      .then(res => {
        const newRoles = res.data;
        setRoles(newRoles || []);
        setHasMore(newRoles && newRoles.length >= 10);
        setHasPrevious(pageNum > 1);
      })
      .catch(error => {
        console.error("Error loading roles:", error);
        setError(t("error_fetching_roles") || error);
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  const goToNextPage = () => {
    if (hasMore && !isLoading) {
      const nextPage = page + 1;
      setPage(nextPage);
      fetchRoles(nextPage);
    }
  };

  const goToPreviousPage = () => {
    if (hasPrevious && !isLoading) {
      const prevPage = page - 1;
      setPage(prevPage);
      fetchRoles(prevPage);
    }
  };

  const handleSearch = (value) => {
    setSearchQuery(value);
    setPage(1);
    setHasMore(true);
    setHasPrevious(false);
  };

  const removeItem = item => {
    request()
      .get("/api/manage/role/"+item.id)
      .then(res => {
        setPage(1);
        setHasMore(true);
        setHasPrevious(false);
        fetchRoles(1);
      })
  }

  if (roles.length === 0 && !isLoading) {
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
    <>
      <div className="my-5">
        <Button onClick={() => props.history.push("/manage-roles/add")}>
          {t("add")}
        </Button>
      </div>
      <div style={{ marginBottom: '15px', display: 'flex', gap: '8px', alignItems: 'center' }}>
        <input
          type="text"
          placeholder={t("searchRolesPlaceholder")}
          value={searchQuery}
          onChange={(e) => handleSearch(e.target.value)}
          className="form-control"
          style={{ maxWidth: '300px' }}
        />
      </div>
      <div className="mt-3">
          <div className="table-header-roles">
            <div className="column-actions-roles">#</div>
            <div className="column-actions-roles">{t("rolename")}</div>
            <div className="column-actions-roles">{t("domain")}</div>
            <div className="column-actions-roles"></div>
          </div>
          <div className='table-body-roles'>
          <div>
          {roles.map((role, index) => (
            <div key={role.id + "-mobile"} className="mobile-table-body-roles">
              <div className="mobile-table-header-roles">
                <div className="column-actions-roles">#</div>
                <div className="column-actions-roles">{t("name")}</div>
                <div className='column-actions-roles'>{t("domain")}</div>
                <div className="column-actions-roles"></div>
              </div>
            <div key={role.id} className="table-row-roles">
              <div className="column-actions-roles">{index + 1}</div>
              <div className="column-actions-roles">{role.name}</div>
              <div className='column-actions-roles'>{role.domain}</div>
              <div className="column-actions-roles">
                <Button 
                  className="btn-info" 
                  size="sm" 
                  onClick={() => {
                    props.history.push({
                      pathname: "/manage-roles/edit",
                      state: {
                        item: role,
                        from: "edit",
                      },
                    });
                  }}>
                  {t("edit")}
                </Button>
                <Button 
                  className="mx-2 btn-danger" 
                  size="sm" 
                  onClick={() => {
                    removeItem(role);
                }}>
                  {t("remove")}
                </Button>
              </div>
            </div>
            </div>
          ))}
          </div>
          <div className="pagination-controls" style={{ display: 'flex', gap: '10px', justifyContent: 'center', marginTop: '20px' }}>
            <Button 
              onClick={goToPreviousPage} 
              disabled={!hasPrevious || isLoading}
              variant="secondary"
            >
              {t("previous")}
            </Button>
            <span style={{ alignSelf: 'center' }}>Page {page}</span>
            <Button 
              onClick={goToNextPage} 
              disabled={!hasMore || isLoading}
              variant="secondary"
            >
              {t("next")}
            </Button>
          </div>
        <div className="spacer"></div>
        </div>
      </div>
    </>
  );
}

export default withRouter(ManageRoles);