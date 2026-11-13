function handleSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.textContent = 'Mengirim...';

  const data = new FormData(e.target);
  const nama = data.get('nama') || '';
  const wa = data.get('wa') || '';
  const minat = data.get('minat') || '';
  const pengalaman = data.get('pengalaman') || '';
  const pesan = data.get('pesan') || '';

  const text = encodeURIComponent(
    'Halo Admin Smart AI, saya ingin daftar minat.\n\n' +
    'Nama: ' + nama +
    '\nWhatsApp: ' + wa +
    '\nMinat: ' + minat +
    '\nPengalaman: ' + pengalaman +
    (pesan ? '\nPesan: ' + pesan : '')
  );

  setTimeout(() => {
    document.getElementById('form-view').style.display = 'none';
    document.getElementById('success-view').classList.add('show');
    window.open('https://wa.me/6282137585596?text=' + text, '_blank');
  }, 600);
}

document.getElementById('interest-form').addEventListener('submit', handleSubmit);
