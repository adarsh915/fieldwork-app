/**
 * Root Navigator
 *
 * Auth-aware navigation:
 *   isLoading = true  → SplashScreen (checking saved token)
 *   user = null       → Auth stack (Login only)
 *   user present      → App stack  (Dashboard, DayStart, etc.)
 *
 * No manual navigation.navigate('Login') or navigate('Dashboard') needed —
 * changing auth state in AuthContext automatically switches the navigator.
 */

/**
 * Backward-compat type exported so existing screens that import
 * RootStackParamList continue to compile without changes.
 */
import { createNativeStackNavigator } from "@react-navigation/native-stack";

import { useAuth } from "../auth/AuthContext";

// Screens
import CalendarScreen from "../screens/CalendarScreen";
import DashboardScreen from "../screens/DashboardScreen";
import DayEndScreen from "../screens/DayEndScreen";
import DayStartScreen from "../screens/DayStartScreen";
import DealerListScreen from "../screens/DealerListScreen";
import LoginScreen from "../screens/LoginScreen";
import OrderScreen from "../screens/OrderScreen";
import ProductListScreen from "../screens/ProductListScreen";
import RewardScreen from "../screens/RewardScreen";
import SplashScreen from "../screens/SplashScreen";
import TrackScreen from "../screens/TrackScreen";
import VisitScreen from "../screens/VisitScreen";

export type RootStackParamList = {
  // App screens
  Dashboard: undefined;
  DayStart: undefined;
  DayEnd: undefined;
  DealerList: undefined;
  ProductList: undefined;
  Visit: {
    partyId: number;
    partyName: string;
    partyMobile: string | null;
    partyAddress: string | null;
  };
  Order: { visitId: number; partyId: number };
  Track: undefined;
  Reward: undefined;
  Calendar: undefined;
  // Admin screens (future)
  AdminDashboard: undefined;
  EmployeeList: undefined;
  CreateEmployee: undefined;
};

// ─── Param lists ───────────────────────────────────────────────────────────────

export type AuthStackParamList = {
  Login: undefined;
};

export type AppStackParamList = {
  Dashboard: undefined;
  DayStart: undefined;
  DayEnd: undefined;
  DealerList: undefined;
  ProductList: undefined;
  Visit: {
    partyId: number;
    partyName: string;
    partyMobile: string | null;
    partyAddress: string | null;
  };
  Order: { visitId: number; partyId: number };
  Track: undefined;
  Reward: undefined;
  Calendar: undefined;
};

// ─── Stacks ────────────────────────────────────────────────────────────────────

const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const AppStack = createNativeStackNavigator<AppStackParamList>();

function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
    </AuthStack.Navigator>
  );
}

function AppNavigator() {
  return (
    <AppStack.Navigator
      initialRouteName="Dashboard"
      screenOptions={{ headerShown: false }}
    >
      <AppStack.Screen name="Dashboard" component={DashboardScreen} />
      <AppStack.Screen name="DayStart" component={DayStartScreen} />
      <AppStack.Screen name="DayEnd" component={DayEndScreen} />
      <AppStack.Screen name="DealerList" component={DealerListScreen} />
      <AppStack.Screen name="ProductList" component={ProductListScreen} />
      <AppStack.Screen name="Visit" component={VisitScreen} />
      <AppStack.Screen name="Order" component={OrderScreen} />
      <AppStack.Screen name="Track" component={TrackScreen} />
      <AppStack.Screen name="Reward" component={RewardScreen} />
      <AppStack.Screen name="Calendar" component={CalendarScreen} />
    </AppStack.Navigator>
  );
}

// ─── Root ──────────────────────────────────────────────────────────────────────

const RootStack = createNativeStackNavigator();

export default function RootNavigator() {
  const { user, isLoading } = useAuth();

  return (
    <RootStack.Navigator
      screenOptions={{ headerShown: false, animation: "fade" }}
    >
      {isLoading ? (
        // Show splash while restoring session
        <RootStack.Screen name="Splash" component={SplashScreen} />
      ) : user ? (
        // Logged in → app screens
        <RootStack.Screen name="App" component={AppNavigator} />
      ) : (
        // Not logged in → auth screens
        <RootStack.Screen name="Auth" component={AuthNavigator} />
      )}
    </RootStack.Navigator>
  );
}
