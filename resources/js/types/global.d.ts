import { PageProps as InertiaPageProps } from "@inertiajs/core";
import { AxiosInstance } from "axios";
import { route as ziggyRoute } from "ziggy-js";
import { PageProps as AppPageProps } from "./";
import Pusher from "pusher-js";
import Echo from "laravel-echo";

declare global {
  interface Window {
    axios: AxiosInstance;
    Pusher: unknown;
    Echo: Echo;
  }

  /* eslint-disable no-var */
  var route: typeof ziggyRoute;
}

declare module "@inertiajs/core" {
  interface PageProps extends InertiaPageProps, AppPageProps {}
}
