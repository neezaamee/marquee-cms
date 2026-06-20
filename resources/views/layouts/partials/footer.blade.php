<footer class="footer">
  <div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
    <div class="col-12 col-sm-auto text-center">
      <p class="mb-0 text-600">Marquee CMS <span class="d-none d-sm-inline-block">| </span><br class="d-sm-none" /> {{ date('Y') }} &copy; All Rights Reserved.</p>
    </div>
    <div class="col-12 col-sm-auto text-center">
      <p class="mb-0 text-600">v{{ config('app.version', '1.2.0') }} | IP: {{ request()->ip() }} (Falcon v3.26.0)</p>
    </div>
  </div>
</footer>
