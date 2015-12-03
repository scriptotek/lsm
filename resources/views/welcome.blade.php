<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Simple Catalogue Search</title>
  <link rel="icon" type="image/png" href="images/favicon-32x32.png" sizes="32x32" />
  <link rel="icon" type="image/png" href="images/favicon-16x16.png" sizes="16x16" />
  <link href='/vendor/latrell/swagger/css/typography.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='/vendor/latrell/swagger/css/reset.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='/vendor/latrell/swagger/css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='/vendor/latrell/swagger/css/reset.css' media='print' rel='stylesheet' type='text/css'/>
  <link href='/vendor/latrell/swagger/css/print.css' media='print' rel='stylesheet' type='text/css'/>
  <script src='/vendor/latrell/swagger/lib/jquery-1.8.0.min.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/jquery.slideto.min.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/jquery.wiggle.min.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/handlebars-2.0.0.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/underscore-min.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/backbone-min.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/swagger-ui.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/highlight.7.3.pack.js' type='text/javascript'></script>
  <script src='/vendor/latrell/swagger/lib/marked.js' type='text/javascript'></script>

  <script type="text/javascript">
    $(function () {
      window.swaggerUi = new SwaggerUi({
        url: '/swagger.json',
        dom_id: 'swagger-ui-container',
        supportedSubmitMethods: ['get'],
        onComplete: function(swaggerApi, swaggerUi){

          $('pre code').each(function(i, e) {
            hljs.highlightBlock(e)
          });

        },
        onFailure: function(data) {
          log("Unable to Load SwaggerUI");
        },
        /*docExpansion: "full",*/
        apisSorter: "alpha",
        showRequestHeaders: false
      });

      window.swaggerUi.load();

      function log() {
        if ('console' in window) {
          console.log.apply(console, arguments);
        }
      }
  });
  </script>
</head>

<body class="swagger-section">
    <div id="message-bar" class="swagger-ui-wrap" data-sw-translate>&nbsp;</div>
    <div id="swagger-ui-container" class="swagger-ui-wrap"></div>
</body>
</html>