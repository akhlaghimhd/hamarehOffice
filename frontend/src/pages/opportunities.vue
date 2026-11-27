function openActivity(item: any) {
  router.push({ path: '/opportunities/' + item.id + '/activities/new', query: { from: 'list' } })
}
