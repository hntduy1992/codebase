const Ziggy = {"url":"http:\/\/localhost:8000","port":8000,"defaults":{},"routes":{"sanctum.csrf-cookie":{"uri":"sanctum\/csrf-cookie","methods":["GET","HEAD"]},"api.test":{"uri":"api\/test","methods":["GET","HEAD"]},"home":{"uri":"\/","methods":["GET","HEAD"]},"login":{"uri":"auth\/login","methods":["GET","HEAD"]},"checkLogin":{"uri":"auth\/login","methods":["POST"]},"dashboard":{"uri":"manage\/dashboard","methods":["GET","HEAD"]},"users.index":{"uri":"manage\/users","methods":["GET","HEAD"]},"logout":{"uri":"manage\/auth\/logout","methods":["POST"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
