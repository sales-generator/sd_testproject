{"version":3, "file":"page_efb0919613a32d0fde286cf8c9bf448d.js", "sections": [{"offset": { "line": 2, "column": 0 }, "map": {"version":3,"file":"/bitrix/components/bitrix/news.detail/templates/flat/script.min.js","sources":["/bitrix/components/bitrix/news.detail/templates/flat/script.js"],"names":["window","JCNewsSlider","containerId","options","container","BX","sliderImages","findChildByClassName","imagesContainerClassName","sliderLeft","leftArrowClassName","sliderRight","rightArrowClassName","sliderControl","controlContainerClassName","slider","Init","prototype","this","time","type","threshold","allowedTime","startX","startY","startTime","bind","delegate","touchStart","touchEnd","sliderControls","findChildren","tagName","proxy","j","func","length","slideTo","slideLeft","slideRight","animation","p","parseInt","style","left","_this","fx","start","finish","callback","res","callback_start","callback_complete","adjustSliderControls","i","addClass","removeClass","e","touchObject","changedTouches","pageX","pageY","Date","getTime","preventDefault","distance","elapsedTime"],"mappings":"CAAA,SAAWA,GAEV,KAAMA,EAAOC,aACb,CACC,OAGDD,EAAOC,aAAe,SAAUC,EAAaC,GAE5C,GAAID,GAAeC,EACnB,CACC,GAAIC,GAAYC,GAAGH,EACnB,IAAIE,EACJ,CACC,GAAIE,GAAeD,GAAGE,qBAAqBH,EAAWD,EAAQK,yBAA0B,KACxF,IAAIC,GAAaJ,GAAGE,qBAAqBH,EAAWD,EAAQO,mBAAoB,KAChF,IAAIC,GAAcN,GAAGE,qBAAqBH,EAAWD,EAAQS,oBAAqB,KAClF,IAAIC,GAAgBR,GAAGE,qBAAqBH,EAAWD,EAAQW,0BAA2B,KAC1F,IAAIR,GAAgBG,GAAcE,GAAeE,EACjD,CACC,GAAIE,GAAS,GAAId,aACjBc,GAAOC,KAAKV,EAAcO,EAAeJ,EAAYE,MAMzDX,GAAOC,aAAagB,UAAUD,KAAO,SAASV,EAAcO,EAAeJ,EAAYE,GAEtFO,KAAKC,KAAO,EACZD,MAAKE,KAAO,aAEZF,MAAKG,UAAY,EACjBH,MAAKI,YAAc,GACnBJ,MAAKK,OAAS,CACdL,MAAKM,OAAS,CACdN,MAAKO,UAAY,CAEjBP,MAAKZ,aAAeA,CACpBD,IAAGqB,KAAKpB,EAAc,aAAcD,GAAGsB,SAAST,KAAKU,WAAYV,MACjEb,IAAGqB,KAAKpB,EAAc,WAAYD,GAAGsB,SAAST,KAAKW,SAAUX,MAE7DA,MAAKY,eAAiBzB,GAAG0B,aAAalB,GAAgBmB,QAAS,MAAO,KACtE,IAAIC,GAAQ,SAASC,EAAGC,GAEvB,MAAO,YAEN,MAAOA,GAAKD,IAGd,KAAK,GAAIA,GAAI,EAAGA,EAAIhB,KAAKY,eAAeM,OAAQF,IAChD,CACC7B,GAAGqB,KAAKR,KAAKY,eAAeI,GAAI,QAASD,EAAMC,EAAG7B,GAAGsB,SAAST,KAAKmB,QAASnB,QAG7EA,KAAKT,WAAaA,CAClBJ,IAAGqB,KAAKjB,EAAY,QAASJ,GAAGsB,SAAST,KAAKoB,UAAWpB,MAEzDA,MAAKP,YAAcA,CACnBN,IAAGqB,KAAKf,EAAa,QAASN,GAAGsB,SAAST,KAAKqB,WAAYrB,OAG5DlB,GAAOC,aAAagB,UAAUsB,WAAa,WAE1C,GAAIrB,KAAKsB,UACR,MACDtB,MAAKsB,UAAY,IAEjB,IAAIC,GAAIC,SAASxB,KAAKZ,aAAaqC,MAAMC,KAAM,GAC/C,IAAIH,IAAM,KAAOvB,KAAKY,eAAeM,OAAO,GAC5C,CACClB,KAAKsB,UAAY,KACjB,QAGD,GAAIK,GAAQ3B,IACZ,IAAKb,IAAGyC,IACPC,MAAON,EACPO,OAAQP,EAAI,IACZtB,KAAMD,KAAKC,KACXC,KAAMF,KAAKE,KACX6B,SAAU,SAASC,GAClBL,EAAMvC,aAAaqC,MAAMC,KAAOM,EAAM,KAEvCC,eAAgB,WACfN,EAAMvC,aAAaqC,MAAMC,KAAOH,EAAI,KAErCW,kBAAmB,WAClBP,EAAMvC,aAAaqC,MAAMC,KAAQH,EAAI,IAAO,GAC5CI,GAAMQ,uBAAuBZ,EAAI,KAAO,IACxCI,GAAML,UAAY,SAEhBO,QAGL/C,GAAOC,aAAagB,UAAUqB,UAAY,WAEzC,GAAIpB,KAAKsB,UACR,MACDtB,MAAKsB,UAAY,IAEjB,IAAIC,GAAIC,SAASxB,KAAKZ,aAAaqC,MAAMC,KAAM,GAC/C,IAAIH,GAAK,EACT,CACCvB,KAAKsB,UAAY,KACjB,QAGD,GAAIK,GAAQ3B,IACZ,IAAKb,IAAGyC,IACPC,MAAON,EACPO,OAAQP,EAAI,IACZtB,KAAMD,KAAKC,KACXC,KAAMF,KAAKE,KACX6B,SAAU,SAASC,GAClBL,EAAMvC,aAAaqC,MAAMC,KAAOM,EAAM,KAEvCC,eAAgB,WACfN,EAAMvC,aAAaqC,MAAMC,KAAOH,EAAI,KAErCW,kBAAmB,WAClBP,EAAMvC,aAAaqC,MAAMC,KAAQH,EAAI,IAAO,GAC5CI,GAAMQ,uBAAuBZ,EAAI,KAAO,IACxCI,GAAML,UAAY,SAEhBO,QAGL/C,GAAOC,aAAagB,UAAUoB,QAAU,SAASH,GAEhD,GAAIhB,KAAKsB,UACR,MACDtB,MAAKsB,UAAY,IAEjB,IAAIC,GAAIC,SAASxB,KAAKZ,aAAaqC,MAAMC,KAAM,GAC/C,IAAIH,IAAOP,EAAI,IACf,CACChB,KAAKsB,UAAY,KACjB,QAGD,GAAIK,GAAQ3B,IACZ,IAAKb,IAAGyC,IACPC,MAAON,EACPO,QAASd,EAAI,IACbf,KAAMD,KAAKC,KACXC,KAAMF,KAAKE,KACX6B,SAAU,SAASC,GAClBL,EAAMvC,aAAaqC,MAAMC,KAAOM,EAAM,KAEvCC,eAAgB,WACfN,EAAMvC,aAAaqC,MAAMC,KAAOH,EAAI,GACpCI,GAAMQ,qBAAqBnB,IAE5BkB,kBAAmB,WAClBP,EAAMvC,aAAaqC,MAAMC,MAASV,EAAI,IAAO,GAC7CW,GAAML,UAAY,SAEhBO,QAGL/C,GAAOC,aAAagB,UAAUoC,qBAAuB,SAASC,GAE7D,IAAK,GAAIpB,GAAI,EAAGA,EAAIhB,KAAKY,eAAeM,OAAQF,IAChD,CACC,GAAIoB,GAAKpB,EACR7B,GAAGkD,SAASrC,KAAKY,eAAeI,GAAI,eAEpC7B,IAAGmD,YAAYtC,KAAKY,eAAeI,GAAI,YAI1ClC,GAAOC,aAAagB,UAAUW,WAAa,SAAS6B,GAEnD,GAAIC,GAAcD,EAAEE,eAAe,EACnCzC,MAAKK,OAASmC,EAAYE,KAC1B1C,MAAKM,OAASkC,EAAYG,KAC1B3C,MAAKO,WAAY,GAAIqC,OAAOC,SAC5BN,GAAEO,iBAGHhE,GAAOC,aAAagB,UAAUY,SAAW,SAAS4B,GAEjD,GAAIC,GAAcD,EAAEE,eAAe,EACnC,IAAIM,GAAWP,EAAYE,MAAQ1C,KAAKK,MACxC,IAAI2C,IAAc,GAAIJ,OAAOC,UAAY7C,KAAKO,SAE9C,IAAIyC,GAAehD,KAAKI,YACxB,CACC,GAAI2C,EAAW/C,KAAKG,UACnBH,KAAKoB,gBACD,IAAI2B,GAAY/C,KAAKG,UACzBH,KAAKqB,aAEPkB,EAAEO,oBAGFhE"}}]}