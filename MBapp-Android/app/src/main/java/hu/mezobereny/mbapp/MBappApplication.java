package hu.mezobereny.mbapp;

import android.app.Application;

import androidx.appcompat.app.AppCompatDelegate;

/**
 * Alkalmazás osztály.
 *
 * <p>A sötét-világos témát a rendszer beállítására bízzuk: a webhely is ezt követi,
 * így a héj és a tartalom együtt vált.</p>
 */
public class MBappApplication extends Application {

    @Override
    public void onCreate() {
        super.onCreate();
        AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_FOLLOW_SYSTEM);
    }
}
