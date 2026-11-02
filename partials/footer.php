<?php
// Closes whatever header.php opened.
$layout = $layout ?? (current_user() ? 'app' : 'auth');
?>
<?php if ($layout === 'public'): ?>
</main>
<footer class="site-foot on-ink">
  <div class="wrap foot-grid">
    <div>
      <a class="brand" href="index.php"><?= egg_mark(24) ?><span>Egg Storage Monitor</span></a>
      <p>A low-cost storage monitor and sales ledger for small-scale egg sellers.</p>
    </div>
    <div>
      <p>A final-year project for the Bachelor of Business in Information Technology, School of Computing and Engineering Sciences, Strathmore University, Nairobi.</p>
    </div>
    <div>
      <p>Student: Stephanie Naliaka Ochoka</p>
      <p>Supervisor: Zainabu Muti</p>
      <p class="fine">This is a prototype for demonstration. Example readings on this page are not live.</p>
    </div>
  </div>
</footer>

<?php elseif ($layout === 'auth'): ?>
    </div>
  </main>
</div>

<?php else: ?>
    </main>
  </div>
</div>
<?php endif; ?>
</body>
</html>
