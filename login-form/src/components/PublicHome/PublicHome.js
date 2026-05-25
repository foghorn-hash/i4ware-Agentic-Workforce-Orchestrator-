import React, { useEffect } from "react";
import { withRouter } from "react-router-dom";
import "video-react/dist/video-react.css";
import "./PublicHome.css";
import { Player } from "video-react";
import videojs from "video.js";
import "video.js/dist/video-js.css";
import LOGO_COPY from "../../PoweredBy_TES_DarkWhite.png";
import { useTranslation } from "react-i18next";


function PublicHome() {
  const { t, i18n } = useTranslation();

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const langFromUrl = urlParams.get("lang");
    if (langFromUrl && ["en", "fi", "sv"].includes(langFromUrl)) {
      i18n.changeLanguage(langFromUrl);
    }
  }, [i18n, urlParams]);

  return (
    <div className="PublicHomePlayer">
      <div className="App-copyright">
        <img src={LOGO_COPY} alt="logo" className="App-logo-copyright" />{" "}
        {t('copyright')}
      </div>
    </div>
  );
}

// export default withRouter(PublicHome);
export default withRouter(PublicHome);
